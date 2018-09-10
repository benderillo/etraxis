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

namespace eTraxis\TemplatesDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use eTraxis\SharedDomain\Model\Collection\Collection;
use eTraxis\SharedDomain\Model\Collection\CollectionInterface;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldRepository extends ServiceEntityRepository implements CollectionInterface
{
    protected $translator;
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RegistryInterface      $registry,
        TranslatorInterface    $translator,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository
    )
    {
        parent::__construct($registry, Field::class);

        $this->translator        = $translator;
        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Field $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Field $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $entity = parent::find($id, $lockMode, $lockVersion);

        if ($entity !== null) {
            $this->warmupCache([$entity]);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $entities = parent::findAll();

        $this->warmupCache($entities);

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $entities = parent::findBy($criteria, $orderBy, $limit, $offset);

        $this->warmupCache($entities);

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $entity = parent::findOneBy($criteria, $orderBy);

        if ($entity !== null) {
            $this->warmupCache([$entity]);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('field');

        // Include states.
        $query->leftJoin('field.state', 'state');
        $query->addSelect('state');

        // Include templates.
        $query->leftJoin('state.template', 'template');
        $query->addSelect('template');

        // Include projects.
        $query->leftJoin('template.project', 'project');
        $query->addSelect('project');

        // Ignore removed fields.
        $query->where('field.removedAt IS NULL');

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->select('COUNT(field.id)');
        $collection->total = (int) $queryTotal->getQuery()->getSingleScalarResult();

        // Sorting.
        foreach ($sort as $property => $direction) {
            $query = $this->queryOrder($query, $property, $direction);
        }

        // Pagination.
        $query->setFirstResult($offset);
        $query->setMaxResults($limit);

        // Execute query.
        $collection->data = $query->getQuery()->getResult();
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

            $query->andWhere($query->expr()->orX(
                'LOWER(field.name) LIKE :search',
                'LOWER(field.description) LIKE :search'
            ));

            $query->setParameter('search', mb_strtolower("%{$search}%"));
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

            case Field::JSON_PROJECT:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('template.project = :project');
                    $query->setParameter('project', (int) $value);
                }

                break;

            case Field::JSON_TEMPLATE:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('state.template = :template');
                    $query->setParameter('template', (int) $value);
                }

                break;

            case Field::JSON_STATE:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('field.state= :state');
                    $query->setParameter('state', (int) $value);
                }

                break;

            case Field::JSON_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(field.name) LIKE LOWER(:name)');
                    $query->setParameter('name', "%{$value}%");
                }

                break;

            case Field::JSON_TYPE:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(field.type) = LOWER(:type)');
                    $query->setParameter('type', $value);
                }

                break;

            case Field::JSON_DESCRIPTION:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(field.description) LIKE LOWER(:description)');
                    $query->setParameter('description', "%{$value}%");
                }

                break;

            case Field::JSON_POSITION:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('field.position = :position');
                    $query->setParameter('position', (int) $value);
                }

                break;

            case Field::JSON_REQUIRED:

                $query->andWhere('field.isRequired = :required');
                $query->setParameter('required', (bool) $value);

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
            Field::JSON_ID          => 'field.id',
            Field::JSON_PROJECT     => 'project.name',
            Field::JSON_TEMPLATE    => 'template.name',
            Field::JSON_STATE       => 'state.name',
            Field::JSON_NAME        => 'field.name',
            Field::JSON_TYPE        => 'field.type',
            Field::JSON_DESCRIPTION => 'field.description',
            Field::JSON_POSITION    => 'field.position',
            Field::JSON_REQUIRED    => 'field.isRequired',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }

    /**
     * Warms up the values cache for specified fields.
     *
     * @param Field[] $fields
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function warmupCache(array $fields): void
    {
        $decimalValues = [];
        $stringValues  = [];
        $textValues    = [];
        $listItems     = [];

        foreach ($fields as $field) {

            switch ($field->type) {

                case FieldType::DECIMAL:
                    $decimalValues[] = $field->parameters->parameter1;
                    $decimalValues[] = $field->parameters->parameter2;
                    $decimalValues[] = $field->parameters->defaultValue;
                    break;

                case FieldType::STRING:
                    $stringValues[] = $field->parameters->defaultValue;
                    break;

                case FieldType::TEXT:
                    $textValues[] = $field->parameters->defaultValue;
                    break;

                case FieldType::LIST:
                    $listItems[] = $field->parameters->defaultValue;
                    break;
            }
        }

        $this->decimalRepository->warmup($decimalValues);
        $this->stringRepository->warmup($stringValues);
        $this->textRepository->warmup($textValues);
        $this->listRepository->warmup($listItems);
    }
}
