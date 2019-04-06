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

/**
 * @covers \eTraxis\SecurityDomain\Application\CommandHandler\Users\DeleteUserHandler::handle
 */
class DeleteUserCommandTest extends TransactionalTestCase
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
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('hstroman@example.com');
        self::assertNotNull($user);

        $command = new DeleteUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $user = $this->repository->findOneByUsername('hstroman@example.com');
        self::assertNull($user);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteUserCommand([
            'user' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('hstroman@example.com');

        $command = new DeleteUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testForbidden()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->repository->findOneByUsername('admin@example.com');

        $command = new DeleteUserCommand([
            'user' => $user->id,
        ]);

        $this->commandBus->handle($command);
    }
}
