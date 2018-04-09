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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateProfileCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('nhills@example.com');

        self::assertSame('nhills@example.com', $user->email);
        self::assertSame('Nikko Hills', $user->fullname);

        $command = new UpdateProfileCommand([
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('chaim.willms@example.com', $user->email);
        self::assertSame('Chaim Willms', $user->fullname);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $command = new UpdateProfileCommand([
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandbus->handle($command);
    }

    public function testExternalAccount()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('einstein@ldap.forumsys.com');

        $command = new UpdateProfileCommand([
            'email'    => 'chaim.willms@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandbus->handle($command);
    }

    public function testUsernameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Account with specified email already exists.');

        $this->loginAs('nhills@example.com');

        $command = new UpdateProfileCommand([
            'email'    => 'vparker@example.com',
            'fullname' => 'Chaim Willms',
        ]);

        $this->commandbus->handle($command);
    }
}
