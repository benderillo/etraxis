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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class SetPasswordCommandTest extends TransactionalTestCase
{
    public function testSuccessAsAdmin()
    {
        $this->loginAs('admin@example.com');

        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));

        $command = new SetPasswordCommand([
            'id'       => $user->id,
            'password' => 'newone',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'newone'));
    }

    public function testSuccessAsOwner()
    {
        $this->loginAs('artem@example.com');

        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));

        $command = new SetPasswordCommand([
            'id'       => $user->id,
            'password' => 'newone',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'newone'));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('admin@example.com');

        $command = new SetPasswordCommand([
            'id'       => $user->id,
            'password' => 'secret',
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetPasswordCommand([
            'id'       => self::UNKNOWN_ENTITY_ID,
            'password' => 'secret',
        ]);

        $this->commandbus->handle($command);
    }

    public function testExternalUser()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('einstein@ldap.forumsys.com');

        $command = new SetPasswordCommand([
            'id'       => $user->id,
            'password' => 'secret',
        ]);

        $this->commandbus->handle($command);
    }

    public function testInvalidPassword()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        $command = new SetPasswordCommand([
            'id'       => $user->id,
            'password' => str_repeat('*', BCryptPasswordEncoder::MAX_PASSWORD_LENGTH + 1),
        ]);

        $this->commandbus->handle($command);
    }
}
