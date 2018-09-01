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
use eTraxis\TemplatesDomain\Model\Entity\Project;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProjectRepository extends ServiceEntityRepository implements CollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Project $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Project $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('project');

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->select('COUNT(project.id)');
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
                'LOWER(project.name) LIKE :search',
                'LOWER(project.description) LIKE :search'
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

            case Project::JSON_NAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(project.name) LIKE LOWER(:name)');
                    $query->setParameter('name', mb_strtolower("%{$value}%"));
                }

                break;

            case Project::JSON_DESCRIPTION:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(project.description) LIKE LOWER(:description)');
                    $query->setParameter('description', mb_strtolower("%{$value}%"));
                }

                break;

            case Project::JSON_SUSPENDED:

                $query->andWhere('project.isSuspended = :suspended');
                $query->setParameter('suspended', $value);

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
            Project::JSON_ID          => 'project.id',
            Project::JSON_NAME        => 'project.name',
            Project::JSON_DESCRIPTION => 'project.description',
            Project::JSON_CREATED     => 'project.createdAt',
            Project::JSON_SUSPENDED   => 'project.isSuspended',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
