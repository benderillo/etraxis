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

class GetUserTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $expected = [
            'id'          => $user->id,
            'email'       => 'artem@example.com',
            'fullname'    => 'Artem Rodygin',
            'description' => null,
            'admin'       => false,
            'disabled'    => false,
            'locked'      => false,
            'provider'    => 'etraxis',
            'locale'      => 'en_US',
            'theme'       => 'azure',
            'timezone'    => 'UTC',
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/users/%s', $user->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/users/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
