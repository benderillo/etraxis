<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\IssuesDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\Comment;
use eTraxis\IssuesDomain\Model\Entity\Dependency;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SharedDomain\Model\Collection\Collection;
use eTraxis\SharedDomain\Model\Collection\CollectionInterface;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IssueRepository extends ServiceEntityRepository implements CollectionInterface
{
    protected $tokens;
    protected $changeRepository;
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RegistryInterface      $registry,
        TokenStorageInterface  $tokens,
        ChangeRepository       $changeRepository,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository
    )
    {
        parent::__construct($registry, Issue::class);

        $this->tokens            = $tokens;
        $this->changeRepository  = $changeRepository;
        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Issue $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Issue $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * Sets new subject of the specified issue.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param Issue  $issue   Issie whose subject is being set.
     * @param Event  $event   Event related to this change.
     * @param string $subject New subject.
     */
    public function changeSubject(Issue $issue, Event $event, string $subject): void
    {
        if ($issue->subject !== $subject) {

            $oldValue = $this->stringRepository->get($issue->subject)->id;
            $newValue = $this->stringRepository->get($subject)->id;

            $change = new Change($event, null, $oldValue, $newValue);

            $issue->subject = $subject;
            $issue->touch();

            $this->changeRepository->persist($change);
            $this->persist($issue);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        $collection = new Collection();

        $query = $this->createQueryBuilder('issue');

        // Include states.
        $query->innerJoin('issue.state', 'state');
        $query->addSelect('state');

        // Include templates.
        $query->innerJoin('state.template', 'template');
        $query->addSelect('template');

        // Include projects.
        $query->innerJoin('template.project', 'project');
        $query->addSelect('project');

        // Include author.
        $query->innerJoin('issue.author', 'author');
        $query->addSelect('author');

        // Include responsible.
        $query->leftJoin('issue.responsible', 'responsible');
        $query->addSelect('responsible');

        // Retrieve only issues the user is allowed to see.
        $query
            ->leftJoin('template.rolePermissionsCollection', 'trp', Join::WITH, 'trp.permission = :permission')
            ->leftJoin('template.groupPermissionsCollection', 'tgp', Join::WITH, 'tgp.permission = :permission')
            ->andWhere($query->expr()->orX(
                'issue.author = :user',
                'issue.responsible = :user',
                'trp.role = :role',
                $query->expr()->in('tgp.group', ':groups')
            ))
            ->setParameters([
                'permission' => TemplatePermission::VIEW_ISSUES,
                'role'       => SystemRole::ANYONE,
                'user'       => $user,
                'groups'     => $user->groups,
            ]);

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->distinct();
        $queryTotal->select('issue.id');
        $collection->total = count($queryTotal->getQuery()->execute());

        // Issues age.
        $query->addSelect('CEIL((COALESCE(issue.closedAt, :now) - issue.createdAt) / 86400) AS age');
        $query->setParameter('now', time());

        // Sorting.
        foreach ($sort as $property => $direction) {
            $query = $this->queryOrder($query, $property, $direction);
        }

        // Pagination.
        $query->setFirstResult($offset);
        $query->setMaxResults($limit);

        // Execute query.
        $collection->data = array_map(function ($entry) {
            return reset($entry);
        }, $query->getQuery()->getResult());

        $collection->from = $offset;
        $collection->to   = count($collection->data) + $offset - 1;

        return $collection;
    }

    /**
     * Alters query in accordance with the specified search.
     *
     * @param QueryBuilder $query
     * @param string       $search
     *
     * @return QueryBuilder
     */
    protected function querySearch(QueryBuilder $query, ?string $search): QueryBuilder
    {
        if (mb_strlen($search) !== 0) {

            // Search in comments.
            $comments = $this->getEntityManager()->createQueryBuilder()
                ->select('issue.id')
                ->from(Comment::class, 'comment')
                ->innerJoin('comment.event', 'event')
                ->innerJoin('event.issue', 'issue')
                ->where('LOWER(comment.body) LIKE LOWER(:search)')
                ->andWhere('comment.isPrivate = :isPrivate')
                ->setParameters([
                    'search'    => "%{$search}%",
                    'isPrivate' => false,
                ]);

            $issues = array_map(function ($entry) {
                return $entry['id'];
            }, $comments->getQuery()->execute());

            $query->andWhere($query->expr()->orX(
                'LOWER(issue.subject) LIKE :search',
                $query->expr()->in('issue', ':comments')
            ));

            $query->setParameter('search', mb_strtolower("%{$search}%"));
            $query->setParameter('comments', $issues);
        }

        return $query;
    }

    /**
     * Alters query to filter by the specified property.
     *
     * @param QueryBuilder $query
     * @param string       $property
     * @param mixed        $value
     *
     * @return QueryBuilder
     */
    protected function queryFilter(QueryBuilder $query, string $property, $value = null): QueryBuilder
    {
        switch ($property) {

            case Issue::JSON_ID:

                if (mb_strlen($value) !== 0) {
                    // Issues human-readable ID.
                    $query->andWhere('LOWER(CONCAT(template.prefix, \'-\', LPAD(CONCAT(\'\', issue.id), GREATEST(3, LENGTH(CONCAT(\'\', issue.id))), \'0\'))) LIKE LOWER(:full_id)');
                    $query->setParameter('full_id', "%{$value}%");
                }

                break;

            case Issue::JSON_SUBJECT:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(issue.subject) LIKE LOWER(:subject)');
                    $query->setParameter('subject', "%{$value}%");
                }

                break;

            case Issue::JSON_AUTHOR:

                $query->andWhere('issue.author = :author');
                $query->setParameter('author', (int) $value);

                break;

            case Issue::JSON_AUTHOR_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(author.fullname) LIKE LOWER(:author_name)');
                    $query->setParameter('author_name', "%{$value}%");
                }

                break;

            case Issue::JSON_PROJECT:

                $query->andWhere('template.project = :project');
                $query->setParameter('project', (int) $value);

                break;

            case Issue::JSON_PROJECT_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(project.name) LIKE LOWER(:project_name)');
                    $query->setParameter('project_name', "%{$value}%");
                }

                break;

            case Issue::JSON_TEMPLATE:

                $query->andWhere('state.template = :template');
                $query->setParameter('template', (int) $value);

                break;

            case Issue::JSON_TEMPLATE_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(template.name) LIKE LOWER(:template_name)');
                    $query->setParameter('template_name', "%{$value}%");
                }

                break;

            case Issue::JSON_STATE:

                $query->andWhere('issue.state= :state');
                $query->setParameter('state', (int) $value);

                break;

            case Issue::JSON_STATE_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(state.name) LIKE LOWER(:state_name)');
                    $query->setParameter('state_name', "%{$value}%");
                }

                break;

            case Issue::JSON_RESPONSIBLE:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('issue.responsible IS NULL');
                }
                else {
                    $query->andWhere('issue.responsible = :responsible');
                    $query->setParameter('responsible', (int) $value);
                }

                break;

            case Issue::JSON_RESPONSIBLE_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(responsible.fullname) LIKE LOWER(:responsible_name)');
                    $query->setParameter('responsible_name', "%{$value}%");
                }

                break;

            case Issue::JSON_IS_CLONED:

                $query->andWhere($value ? 'issue.origin IS NOT NULL' : 'issue.origin IS NULL');

                break;

            case Issue::JSON_AGE:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('CEIL((COALESCE(issue.closedAt, :now) - issue.createdAt) / 86400) = :age');
                    $query->setParameter('age', (int) $value);
                    $query->setParameter('now', time());
                }

                break;

            case Issue::JSON_IS_CRITICAL:

                if ($value) {
                    $expr = $query->expr()->andX(
                        'template.criticalAge IS NOT NULL',
                        'issue.closedAt IS NULL',
                        'template.criticalAge < CEIL((COALESCE(issue.closedAt, :now) - issue.createdAt) / 86400)'
                    );
                }
                else {
                    $expr = $query->expr()->orX(
                        'template.criticalAge IS NULL',
                        'issue.closedAt IS NOT NULL',
                        'template.criticalAge >= CEIL((COALESCE(issue.closedAt, :now) - issue.createdAt) / 86400)'
                    );
                }

                $query->andWhere($expr);
                $query->setParameter('now', time());

                break;

            case Issue::JSON_IS_SUSPENDED:

                if ($value) {
                    $expr = $query->expr()->andX(
                        'issue.resumesAt IS NOT NULL',
                        'issue.resumesAt > :now'
                    );
                }
                else {
                    $expr = $query->expr()->orX(
                        'issue.resumesAt IS NULL',
                        'issue.resumesAt <= :now'
                    );
                }

                $query->andWhere($expr);
                $query->setParameter('now', time());

                break;

            case Issue::JSON_IS_CLOSED:

                $query->andWhere($value ? 'issue.closedAt IS NOT NULL' : 'issue.closedAt IS NULL');

                break;

            case Issue::JSON_DEPENDENCY:

                $dependencies = $this->getEntityManager()->createQueryBuilder()
                    ->select('dependency')
                    ->from(Dependency::class, 'dependency')
                    ->where('dependency.issue = :issue')
                    ->setParameter('issue', (int) $value);

                $issues = array_map(function (Dependency $entry) {
                    return $entry->dependency->id;
                }, $dependencies->getQuery()->execute());

                $query->andWhere($query->expr()->in('issue', ':dependencies'));
                $query->setParameter('dependencies', $issues);

                break;
        }

        return $query;
    }

    /**
     * Alters query in accordance with the specified sorting.
     *
     * @param QueryBuilder $query
     * @param string       $property
     * @param string       $direction
     *
     * @return QueryBuilder
     */
    protected function queryOrder(QueryBuilder $query, string $property, ?string $direction): QueryBuilder
    {
        $map = [
            Issue::JSON_ID          => 'issue.id',
            Issue::JSON_SUBJECT     => 'issue.subject',
            Issue::JSON_CREATED_AT  => 'issue.createdAt',
            Issue::JSON_CHANGED_AT  => 'issue.changedAt',
            Issue::JSON_CLOSED_AT   => 'issue.closedAt',
            Issue::JSON_AUTHOR      => 'author.fullname',
            Issue::JSON_PROJECT     => 'project.name',
            Issue::JSON_TEMPLATE    => 'template.name',
            Issue::JSON_STATE       => 'state.name',
            Issue::JSON_RESPONSIBLE => 'responsible.fullname',
            Issue::JSON_AGE         => 'age',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
