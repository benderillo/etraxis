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

/** @noinspection PhpUndefinedMethodInspection */

namespace eTraxis\TemplatesDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Repository\CacheTrait
 */
class CacheTraitTest extends TransactionalTestCase
{
    /** @var ServiceEntityRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $doctrine = $this->doctrine;

        $this->repository = new class($doctrine) extends ServiceEntityRepository {
            use CacheTrait;

            protected $calls = 0;

            public function __construct(RegistryInterface $registry)
            {
                parent::__construct($registry, User::class);
                $this->createCache();
            }

            public function getCache(): CacheInterface
            {
                return $this->cache;
            }

            public function getCalls(): int
            {
                return $this->calls;
            }

            public function find($id, $lockMode = null, $lockVersion = null)
            {
                return $this->findInCache($id, function ($id) {
                    $this->calls++;

                    return parent::find($id);
                });
            }

            public function delete($id): bool
            {
                return $this->deleteFromCache($id);
            }

            public function warmup(array $ids): int
            {
                return $this->warmupCache($this, $ids);
            }
        };
    }

    /**
     * @covers ::createCache
     */
    public function testCreateCache()
    {
        self::assertNotNull($this->repository->getCache());
    }

    /**
     * @covers ::findInCache
     */
    public function testFindInCache()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        self::assertNull($this->repository->find(null));
        self::assertSame(0, $this->repository->getCalls());

        $first = $this->repository->find($user->id);
        self::assertSame($user, $first);
        self::assertSame(1, $this->repository->getCalls());

        $second = $this->repository->find($user->id);
        self::assertSame($user, $second);
        self::assertSame(1, $this->repository->getCalls());
    }

    /**
     * @covers ::deleteFromCache
     */
    public function testDeleteFromCache()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        self::assertSame(0, $this->repository->getCalls());

        $first = $this->repository->find($user->id);
        self::assertSame($user, $first);
        self::assertSame(1, $this->repository->getCalls());

        self::assertFalse($this->repository->delete(null));
        self::assertTrue($this->repository->delete($user->id));

        $second = $this->repository->find($user->id);
        self::assertSame($user, $second);
        self::assertSame(2, $this->repository->getCalls());
    }

    /**
     * @covers ::warmupCache
     */
    public function testWarmupCache()
    {
        /** @var User $user1 */
        $user1 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        /** @var User $user2 */
        $user2 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        /** @var User $user3 */
        $user3 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'einstein@ldap.forumsys.com']);

        self::assertSame(2, $this->repository->warmup([
            self::UNKNOWN_ENTITY_ID,
            $user1->id,
            $user2->id,
        ]));

        self::assertSame(0, $this->repository->getCalls());

        $first = $this->repository->find($user1->id);
        self::assertSame($user1, $first);
        self::assertSame(0, $this->repository->getCalls());

        $second = $this->repository->find($user2->id);
        self::assertSame($user2, $second);
        self::assertSame(0, $this->repository->getCalls());

        $third = $this->repository->find($user3->id);
        self::assertSame($user3, $third);
        self::assertSame(1, $this->repository->getCalls());
    }
}
