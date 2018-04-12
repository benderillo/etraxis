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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\WebTestCase;

class UserRepositoryTest extends WebTestCase
{
    /** @var UserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testFindOneByUsernameSuccess()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('admin@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('eTraxis Admin', $user->fullname);
    }

    public function testFindOneByUsernameUnknown()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('404@example.com');

        self::assertNull($user);
    }
}
