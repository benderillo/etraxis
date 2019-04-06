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
use Doctrine\ORM\QueryBuilder;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Entity\Watcher;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SharedDomain\Model\Collection\Collection;
use eTraxis\SharedDomain\Model\Collection\CollectionInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class WatcherRepository extends ServiceEntityRepository implements CollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Watcher::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(Watcher $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('watcher');

        // Include user.
        $query->innerJoin('watcher.user', 'user');
        $query->addSelect('user');

        // Search.
        $this->querySearch($query, $search);

        // Filter.
        foreach ($filter as $property => $value) {
            $this->queryFilter($query, $property, $value);
        }

        // Total number of entities.
        $queryTotal = clone $query;
        $queryTotal->select('COUNT(user.id)');
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
                'LOWER(user.email) LIKE :search',
                'LOWER(user.fullname) LIKE :search'
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

            case Issue::JSON_ID:

                $query->andWhere('watcher.issue = :issue');
                $query->setParameter('issue', (int) $value);

                break;

            case User::JSON_EMAIL:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(user.email) LIKE LOWER(:email)');
                    $query->setParameter('email', "%{$value}%");
                }

                break;

            case User::JSON_FULLNAME:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(user.fullname) LIKE LOWER(:fullname)');
                    $query->setParameter('fullname', "%{$value}%");
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
            User::JSON_EMAIL    => 'user.email',
            User::JSON_FULLNAME => 'user.fullname',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
