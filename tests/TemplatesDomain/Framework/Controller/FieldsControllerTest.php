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

namespace eTraxis\TemplatesDomain\Framework\Controller;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldsControllerTest extends WebTestCase
{
    public function testPermissions()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Details'], ['id' => 'ASC']);

        $expected = [
            'update'      => true,
            'delete'      => true,
            'permissions' => true,
        ];

        $uri = sprintf('/admin/fields/permissions/%s', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $this->loginAs('artem@example.com');

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->loginAs('admin@example.com');

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }
}
