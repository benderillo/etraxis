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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiUsersController;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\SecurityDomain\Framework\Controller\ApiUsersController::deleteUser
 */
class DeleteUserTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);
        self::assertNotNull($user);

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->clear();

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);
        self::assertNull($user);
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'hstroman@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
