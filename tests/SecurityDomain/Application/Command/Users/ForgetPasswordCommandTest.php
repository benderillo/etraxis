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

namespace eTraxis\SecurityDomain\Application\Command\Users;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;

class ForgetPasswordCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess()
    {
        $command = new ForgetPasswordCommand([
            'email' => 'artem@example.com',
        ]);

        $token = $this->commandBus->handle($command);
        self::assertRegExp('/^([0-9a-f]{32}$)/', $token);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('artem@example.com');
        self::assertTrue($user->isResetTokenValid($token));
    }

    public function testExternal()
    {
        $user = $this->repository->findOneByUsername('einstein@ldap.forumsys.com');
        self::assertNotNull($user);

        $command = new ForgetPasswordCommand([
            'email' => 'einstein@ldap.forumsys.com',
        ]);

        $token = $this->commandBus->handle($command);
        self::assertNull($token);

        $users = $this->repository->findBy(['resetToken' => null]);
        self::assertCount(count($this->repository->findAll()), $users);
    }

    public function testUnknown()
    {
        $user = $this->repository->findOneByUsername('404@example.com');
        self::assertNull($user);

        $command = new ForgetPasswordCommand([
            'email' => '404@example.com',
        ]);

        $token = $this->commandBus->handle($command);
        self::assertNull($token);

        $users = $this->repository->findBy(['resetToken' => null]);
        self::assertCount(count($this->repository->findAll()), $users);
    }
}
