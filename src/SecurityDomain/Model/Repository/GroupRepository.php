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

namespace eTraxis\SecurityDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SharedDomain\Model\Collection\Collection;
use eTraxis\SharedDomain\Model\Collection\CollectionInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GroupRepository extends ServiceEntityRepository implements CollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(Group $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(Group $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('grp');

        // Include projects.
        $query->leftJoin('grp.project', 'project');
        $query->addSelect('project');

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->select('COUNT(grp.id)');
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
                'LOWER(grp.name) LIKE :search',
                'LOWER(grp.description) LIKE :search'
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

            case Group::JSON_PROJECT:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('grp.project IS NULL');
                }
                else {
                    $query->andWhere('grp.project = :project');
                    $query->setParameter('project', (int) $value);
                }

                break;

            case Group::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('grp.name IS NULL');
                }
                else {
                    $query->andWhere('LOWER(grp.name) LIKE LOWER(:name)');
                    $query->setParameter('name', "%{$value}%");
                }

                break;

            case Group::JSON_DESCRIPTION:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('grp.description IS NULL');
                }
                else {
                    $query->andWhere('LOWER(grp.description) LIKE LOWER(:description)');
                    $query->setParameter('description', "%{$value}%");
                }

                break;

            case Group::JSON_GLOBAL:

                $query->andWhere($value ? 'grp.project IS NULL' : 'grp.project IS NOT NULL');

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
            Group::JSON_ID          => 'grp.id',
            Group::JSON_PROJECT     => 'project.name',
            Group::JSON_NAME        => 'grp.name',
            Group::JSON_DESCRIPTION => 'grp.description',
            Group::JSON_GLOBAL      => 'project.id - project.id',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
