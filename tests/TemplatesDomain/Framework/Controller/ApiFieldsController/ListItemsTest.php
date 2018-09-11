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
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListItemsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $expected = array_map(function (ListItem $item) {
            return $item->text;
        }, $this->doctrine->getRepository(ListItem::class)->findBy(['field' => $field]));

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $actual  = array_map(function (array $row) {
            return $row['text'];
        }, $content);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function test401()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s/items', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
