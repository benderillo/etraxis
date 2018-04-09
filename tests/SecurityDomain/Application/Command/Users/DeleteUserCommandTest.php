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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteUserCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('nhills@example.com');
        self::assertNotNull($user);

        $command = new DeleteUserCommand([
            'id' => $user->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $user = $repository->findOneByUsername('nhills@example.com');
        self::assertNull($user);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteUserCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('nhills@example.com');

        $command = new DeleteUserCommand([
            'id' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testForbidden()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('admin@example.com');

        $command = new DeleteUserCommand([
            'id' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }
}
