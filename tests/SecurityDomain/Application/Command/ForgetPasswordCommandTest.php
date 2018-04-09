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

namespace eTraxis\SecurityDomain\Application\Command;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;

class ForgetPasswordCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $command = new ForgetPasswordCommand([
            'email' => 'artem@example.com',
        ]);

        $token = $this->commandbus->handle($command);
        self::assertRegExp('/^([0-9a-f]{32}$)/', $token);

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');
        self::assertTrue($user->isResetTokenValid($token));
    }

    public function testExternal()
    {
        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        $user = $repository->findOneByUsername('einstein@ldap.forumsys.com');
        self::assertNotNull($user);

        $command = new ForgetPasswordCommand([
            'email' => 'einstein@ldap.forumsys.com',
        ]);

        $token = $this->commandbus->handle($command);
        self::assertNull($token);

        $users = $repository->findBy(['resetToken' => null]);
        self::assertCount(count($repository->findAll()), $users);
    }

    public function testUnknown()
    {
        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        $user = $repository->findOneByUsername('404@example.com');
        self::assertNull($user);

        $command = new ForgetPasswordCommand([
            'email' => '404@example.com',
        ]);

        $token = $this->commandbus->handle($command);
        self::assertNull($token);

        $users = $repository->findBy(['resetToken' => null]);
        self::assertCount(count($repository->findAll()), $users);
    }
}
