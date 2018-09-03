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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiTemplatesController;

use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListTemplatesTest extends WebTestCase
{
    public function testSuccess()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
            ['Development', 'Development Task B'],
            ['Support',     'Support Request B'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $actual  = array_map(function (array $row) {
            return [$row['name'], $row['description']];
        }, $content['data']);

        self::assertSame(0, $content['from']);
        self::assertSame(7, $content['to']);
        self::assertSame(8, $content['total']);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        $uri = '/api/templates';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
