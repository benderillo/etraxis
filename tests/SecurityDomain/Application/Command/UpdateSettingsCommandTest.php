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

class UpdateSettingsCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('artem@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');

        self::assertSame('en_US', $user->locale);
        self::assertSame('azure', $user->theme);
        self::assertSame('UTC', $user->timezone);

        $command = new UpdateSettingsCommand([
            'locale'   => 'ru',
            'theme'    => 'humanity',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        self::assertSame('ru', $user->locale);
        self::assertSame('humanity', $user->theme);
        self::assertSame('Pacific/Auckland', $user->timezone);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $command = new UpdateSettingsCommand([
            'locale'   => 'ru',
            'theme'    => 'humanity',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->commandbus->handle($command);
    }
}
