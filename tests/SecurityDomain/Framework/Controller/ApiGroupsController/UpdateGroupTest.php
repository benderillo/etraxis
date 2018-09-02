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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiGroupsController;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateGroupTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $data = [
            'name'        => 'Programmers',
            'description' => $group->description,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s', $group->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($group);

        self::assertSame('Programmers', $group->name);
    }

    public function test400()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s', $group->id);

        $response = $this->json(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $data = [
            'name'        => 'Programmers',
            'description' => $group->description,
        ];

        $uri = sprintf('/api/groups/%s', $group->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $data = [
            'name'        => 'Programmers',
            'description' => $group->description,
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/groups/%s', $group->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $data = [
            'name'        => 'Programmers',
            'description' => $group->description,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $data = [
            'name'        => 'Managers',
            'description' => $group->description,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s', $group->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
