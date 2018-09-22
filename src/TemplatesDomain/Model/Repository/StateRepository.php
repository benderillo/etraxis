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
use eTraxis\TemplatesDomain\Model\Entity\State;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StateRepository extends ServiceEntityRepository implements CollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, State::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(State $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(State $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('state');

        // Include templates.
        $query->innerJoin('state.template', 'template');
        $query->addSelect('template');

        // Include projects.
        $query->innerJoin('template.project', 'project');
        $query->addSelect('project');

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->select('COUNT(state.id)');
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

            $query->where($query->expr()->orX(
                'LOWER(state.name) LIKE :search'
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

            case State::JSON_PROJECT:

                $query->andWhere('template.project = :project');
                $query->setParameter('project', (int) $value);

                break;

            case State::JSON_TEMPLATE:

                $query->andWhere('state.template = :template');
                $query->setParameter('template', (int) $value);

                break;

            case State::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('state.name IS NULL');
                }
                else {
                    $query->andWhere('LOWER(state.name) LIKE LOWER(:name)');
                    $query->setParameter('name', "%{$value}%");
                }

                break;

            case State::JSON_TYPE:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('state.type IS NULL');
                }
                else {
                    $query->andWhere('LOWER(state.type) = LOWER(:type)');
                    $query->setParameter('type', $value);
                }

                break;

            case State::JSON_RESPONSIBLE:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('state.responsible IS NULL');
                }
                else {
                    $query->andWhere('LOWER(state.responsible) = LOWER(:responsible)');
                    $query->setParameter('responsible', $value);
                }

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
            State::JSON_ID          => 'state.id',
            State::JSON_PROJECT     => 'project.name',
            State::JSON_TEMPLATE    => 'template.name',
            State::JSON_NAME        => 'state.name',
            State::JSON_TYPE        => 'state.type',
            State::JSON_RESPONSIBLE => 'state.responsible',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
