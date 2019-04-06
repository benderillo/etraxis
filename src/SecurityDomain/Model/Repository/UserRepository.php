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
use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SharedDomain\Model\Collection\Collection;
use eTraxis\SharedDomain\Model\Collection\CollectionInterface;
use LazySec\Repository\UserRepositoryInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface, CollectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(User $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function remove(User $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function refresh(User $entity): void
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByUsername(string $username): ?UserInterface
    {
        /** @var UserInterface $user */
        $user = $this->findOneBy([
            'email' => $username,
        ]);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection
    {
        $collection = new Collection();

        $query = $this->createQueryBuilder('user');

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
                'LOWER(user.fullname) LIKE :search',
                'LOWER(user.description) LIKE :search',
                'LOWER(user.account.provider) LIKE :search'
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

            case User::JSON_DESCRIPTION:

                if (mb_strlen($value) !== 0) {
                    $query->andWhere('LOWER(user.description) LIKE LOWER(:description)');
                    $query->setParameter('description', "%{$value}%");
                }

                break;

            case User::JSON_ADMIN:

                $query->andWhere('user.role = :role');
                $query->setParameter('role', $value ? User::ROLE_ADMIN : User::ROLE_USER);

                break;

            case User::JSON_DISABLED:

                $query->andWhere('user.isEnabled = :enabled');
                $query->setParameter('enabled', (bool) !$value);

                break;

            case User::JSON_LOCKED:

                if ($value) {
                    $query->andWhere($query->expr()->orX(
                        'user.lockedUntil = 0',             // a) the user is locked for indefinite time
                        'user.lockedUntil > :now'           // b) time, the user is locked until, is still in future
                    ));
                }
                else {
                    $query->andWhere($query->expr()->orX(
                        'user.lockedUntil IS NULL',         // a) the user was never locked
                        $query->expr()->andX(
                            'user.lockedUntil <> 0',        // b) the user is not locked for indefinite time
                            'user.lockedUntil <= :now'      //    and this time is already in past
                        )
                    ));
                }

                $query->setParameter('now', time());

                break;

            case User::JSON_PROVIDER:

                if (AccountProvider::has($value)) {
                    $query->andWhere('LOWER(user.account.provider) = LOWER(:provider)');
                    $query->setParameter('provider', $value);
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
            User::JSON_ID          => 'user.id',
            User::JSON_EMAIL       => 'user.email',
            User::JSON_FULLNAME    => 'user.fullname',
            User::JSON_DESCRIPTION => 'user.description',
            User::JSON_ADMIN       => 'user.role',
            User::JSON_PROVIDER    => 'user.account.provider',
        ];

        if (mb_strtoupper($direction) !== self::SORT_DESC) {
            $direction = self::SORT_ASC;
        }

        return $query->addOrderBy($map[$property], $direction);
    }
}
