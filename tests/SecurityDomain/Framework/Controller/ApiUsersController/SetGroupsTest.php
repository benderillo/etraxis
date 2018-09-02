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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetGroupsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $expected = [
            ['Company Staff',     null],
            ['Developers',        'Developers C'],
            ['Support Engineers', 'Support Engineers A'],
            ['Support Engineers', 'Support Engineers B'],
        ];

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $user->groups);

        self::assertSame($expected, $actual);

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add' => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($user);

        $expected = [
            ['Company Staff',     null],
            ['Developers',        'Developers C'],
            ['Support Engineers', 'Support Engineers B'],
            ['Support Engineers', 'Support Engineers C'],
        ];

        $actual = array_map(function (Group $group) {
            return [$group->name, $group->description];
        }, $user->groups);

        self::assertSame($expected, $actual);
    }

    public function test400()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $data = [
            'add' => [
                'bar1',
                'bar2',
            ],
            'remove' => [
                'foo1',
                'foo2',
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add' => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add' => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/users/%s/groups', $user->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        /** @var Group[] $support */
        $support = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $data = [
            'add' => [
                $support[1]->id,
                $support[2]->id,
            ],
            'remove' => [
                $support[0]->id,
                $support[1]->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/users/%s/groups', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
