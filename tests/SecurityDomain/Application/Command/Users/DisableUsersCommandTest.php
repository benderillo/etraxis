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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DisableUsersCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $nhills */
        /** @var User $tberge */
        $nhills = $repository->findOneByUsername('nhills@example.com');
        $tberge = $repository->findOneByUsername('tberge@example.com');

        self::assertTrue($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());

        $command = new DisableUsersCommand([
            'ids' => [
                $nhills->id,
                $tberge->id,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($nhills);
        $this->doctrine->getManager()->refresh($tberge);

        self::assertFalse($nhills->isEnabled());
        self::assertFalse($tberge->isEnabled());
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('nhills@example.com');

        $command = new DisableUsersCommand([
            'ids' => [
                $user->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new DisableUsersCommand([
            'ids' => [
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testForbidden()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $admin */
        $admin = $repository->findOneByUsername('admin@example.com');

        $command = new DisableUsersCommand([
            'ids' => [
                $admin->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }
}
