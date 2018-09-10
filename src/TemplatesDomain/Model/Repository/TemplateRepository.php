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
use eTraxis\TemplatesDomain\Model\Entity\Template;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TemplateRepository extends ServiceEntityRepository implements CollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Template $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Template $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('template');

        // Include projects.
        $query->leftJoin('template.project', 'project');
        $query->addSelect('project');

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->select('COUNT(template.id)');
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
                'LOWER(template.name) LIKE :search',
                'LOWER(template.prefix) LIKE :search',
                'LOWER(template.description) LIKE :search'
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

            case Template::JSON_PROJECT:

                $query->andWhere('template.project = :project');
                $query->setParameter('project', (int) $value);

                break;

            case Template::JSON_NAME:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('template.name IS NULL');
                }
                else {
                    $query->andWhere('LOWER(template.name) LIKE LOWER(:name)');
                    $query->setParameter('name', "%{$value}%");
                }

                break;

            case Template::JSON_PREFIX:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('template.prefix IS NULL');
                }
                else {
                    $query->andWhere('LOWER(template.prefix) LIKE LOWER(:prefix)');
                    $query->setParameter('prefix', "%{$value}%");
                }

                break;

            case Template::JSON_DESCRIPTION:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('template.description IS NULL');
                }
                else {
                    $query->andWhere('LOWER(template.description) LIKE LOWER(:description)');
                    $query->setParameter('description', "%{$value}%");
                }

                break;

            case Template::JSON_CRITICAL:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('template.criticalAge IS NULL');
                }
                else {
                    $query->andWhere('template.criticalAge = :criticalAge');
                    $query->setParameter('criticalAge', (int) $value);
                }

                break;

            case Template::JSON_FROZEN:

                if (mb_strlen($value) === 0) {
                    $query->andWhere('template.frozenTime IS NULL');
                }
                else {
                    $query->andWhere('template.frozenTime = :frozenTime');
                    $query->setParameter('frozenTime', (int) $value);
                }

                break;

            case Template::JSON_LOCKED:

                $query->andWhere('template.isLocked = :locked');
                $query->setParameter('locked', (bool) $value);

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
            Template::JSON_ID          => 'template.id',
            Template::JSON_PROJECT     => 'project.name',
            Template::JSON_NAME        => 'template.name',
            Template::JSON_PREFIX      => 'template.prefix',
            Template::JSON_DESCRIPTION => 'template.description',
            Template::JSON_CRITICAL    => 'template.criticalAge',
            Template::JSON_FROZEN      => 'template.frozenTime',
            Template::JSON_LOCKED      => 'template.isLocked',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
