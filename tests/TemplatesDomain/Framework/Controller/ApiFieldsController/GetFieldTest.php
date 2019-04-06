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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController::getField
 */
class GetFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $expected = [
            'id'          => $field->id,
            'state'       => [
                'id'          => $field->state->id,
                'template'    => [
                    'id'          => $field->state->template->id,
                    'project'     => [
                        'id'          => $field->state->template->project->id,
                        'name'        => 'Distinctio',
                        'description' => 'Project A',
                        'created'     => $field->state->template->project->createdAt,
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
            ],
            'name'        => 'Details',
            'type'        => 'text',
            'description' => null,
            'position'    => 1,
            'required'    => true,
            'maxlength'   => 250,
            'default'     => null,
            'pcre'        => [
                'check'   => null,
                'search'  => null,
                'replace' => null,
            ],
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
