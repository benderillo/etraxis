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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiStatesController;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StateResponsibleGroup;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetResponsiblesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        self::assertNotSame([$group], array_map(function (StateResponsibleGroup $group) {
            return $group->group;
        }, $state->responsibleGroups));

        $data = [
            'groups' => [
                $group->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/responsibles', $state->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($state);

        self::assertSame([$group], array_map(function (StateResponsibleGroup $group) {
            return $group->group;
        }, $state->responsibleGroups));
    }

    public function testSuccessNone()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        self::assertNotEmpty($state->responsibleGroups);

        $data = [
            'groups' => [],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/responsibles', $state->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($state);

        self::assertEmpty($state->responsibleGroups);
    }

    public function test400()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/responsibles', $state->id);

        $response = $this->json(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'groups' => [],
        ];

        $uri = sprintf('/api/states/%s/responsibles', $state->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'groups' => [],
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/states/%s/responsibles', $state->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $data = [
            'groups' => [],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/responsibles', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
