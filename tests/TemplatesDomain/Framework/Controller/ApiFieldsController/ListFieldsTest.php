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
use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController::listFields
 */
class ListFieldsTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $expected = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $this->doctrine->getRepository(Field::class)->findBy(['removedAt' => null]));

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $actual  = array_map(function (array $row) {
            return [$row['name'], $row['state']['template']['project']['name']];
        }, $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(39, $content['to']);
        self::assertSame(40, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/fields';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
