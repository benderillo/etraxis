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
use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldPermission;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ChangeRepository extends ServiceEntityRepository
{
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RegistryInterface      $registry,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository
    )
    {
        parent::__construct($registry, Change::class);

        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Change $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * Finds all issue changes, visible to specified user.
     *
     * @param Issue $issue
     * @param User  $user
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return Change[]
     */
    public function findAllByIssue(Issue $issue, User $user): array
    {
        $query = $this->createQueryBuilder('change')
            ->innerJoin('change.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.issue', 'issue')
            ->addSelect('issue')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->leftJoin('change.field', 'field')
            ->addSelect('field')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->addOrderBy('field.position', 'ASC');

        // Retrieve only fields the user is allowed to see.
        $query
            ->leftJoin('field.rolePermissionsCollection', 'frp_anyone', Join::WITH, 'frp_anyone.role = :role_anyone')
            ->leftJoin('field.rolePermissionsCollection', 'frp_author', Join::WITH, 'frp_author.role = :role_author')
            ->leftJoin('field.rolePermissionsCollection', 'frp_responsible', Join::WITH, 'frp_responsible.role = :role_responsible')
            ->leftJoin('field.groupPermissionsCollection', 'fgp')
            ->andWhere($query->expr()->orX(
                'change.field IS NULL',
                $query->expr()->in('frp_anyone.permission', [FieldPermission::READ_ONLY, FieldPermission::READ_WRITE]),
                $query->expr()->andX(
                    'issue.author = :user',
                    $query->expr()->in('frp_author.permission', [FieldPermission::READ_ONLY, FieldPermission::READ_WRITE])
                ),
                $query->expr()->andX(
                    'issue.responsible = :user',
                    $query->expr()->in('frp_responsible.permission', [FieldPermission::READ_ONLY, FieldPermission::READ_WRITE])
                ),
                $query->expr()->in('fgp.group', ':groups')
            ));

        $query->setParameters([
            'role_anyone'      => SystemRole::ANYONE,
            'role_author'      => SystemRole::AUTHOR,
            'role_responsible' => SystemRole::RESPONSIBLE,
            'issue'            => $issue,
            'user'             => $user,
            'groups'           => $user->groups,
        ]);

        /** @var Change[] $changes */
        $changes = $query->getQuery()->getResult();

        // Warmup values cache.
        $values = [
            FieldType::DECIMAL => [],
            FieldType::STRING  => [],
            FieldType::TEXT    => [],
            FieldType::LIST    => [],
        ];

        foreach ($changes as $change) {

            if ($change->field === null) {
                $values[FieldType::STRING][] = $change->oldValue;
                $values[FieldType::STRING][] = $change->newValue;
            }
            elseif (array_key_exists($change->field->type, $values)) {
                $values[$change->field->type][] = $change->oldValue;
                $values[$change->field->type][] = $change->newValue;
            }
        }

        $this->decimalRepository->warmup(array_unique($values[FieldType::DECIMAL]));
        $this->stringRepository->warmup(array_unique($values[FieldType::STRING]));
        $this->textRepository->warmup(array_unique($values[FieldType::TEXT]));
        $this->listRepository->warmup(array_unique($values[FieldType::LIST]));

        return $changes;
    }
}
