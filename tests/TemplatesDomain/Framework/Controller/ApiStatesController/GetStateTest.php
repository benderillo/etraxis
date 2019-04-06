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

use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\TemplatesDomain\Framework\Controller\ApiStatesController::getState
 */
class GetStateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $expected = [
            'id'          => $state->id,
            'template'    => [
                'id'          => $state->template->id,
                'project'     => [
                    'id'          => $state->template->project->id,
                    'name'        => 'Distinctio',
                    'description' => 'Project A',
                    'created'     => $state->template->project->createdAt,
                    'suspended'   => true,
                ],
                'name'        => 'Support',
                'prefix'      => 'req',
                'description' => 'Support Request A',
                'critical'    => 3,
                'frozen'      => 7,
                'locked'      => true,
            ],
            'name'        => 'Submitted',
            'type'        => 'initial',
            'responsible' => 'keep',
            'next'        => null,
        ];

        $uri = sprintf('/api/states/%s', $state->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s', $state->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        $uri = sprintf('/api/states/%s', $state->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
