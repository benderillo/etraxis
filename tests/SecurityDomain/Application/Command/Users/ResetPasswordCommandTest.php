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
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class ResetPasswordCommandTest extends TransactionalTestCase
{
    /** @throws \Exception */
    public function testSuccess()
    {
        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($user->isResetTokenValid($token));

        $command = new ResetPasswordCommand([
            'token'    => $token,
            'password' => 'newone',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'newone'));
        self::assertFalse($user->isResetTokenValid($token));
    }

    public function testUnknownToken()
    {
        $this->expectException(NotFoundHttpException::class);

        $command = new ResetPasswordCommand([
            'token'    => Uuid::uuid4()->getHex(),
            'password' => 'secret',
        ]);

        $this->commandbus->handle($command);
    }

    /** @throws \Exception */
    public function testExpiredToken()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT0M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        self::assertFalse($user->isResetTokenValid($token));

        $command = new ResetPasswordCommand([
            'token'    => $token,
            'password' => 'secret',
        ]);

        $this->commandbus->handle($command);
    }

    /** @throws \Exception */
    public function testInvalidPassword()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid password.');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        $token = $user->generateResetToken(new \DateInterval('PT1M'));

        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        $command = new ResetPasswordCommand([
            'token'    => $token,
            'password' => str_repeat('*', BCryptPasswordEncoder::MAX_PASSWORD_LENGTH + 1),
        ]);

        $this->commandbus->handle($command);
    }
}
