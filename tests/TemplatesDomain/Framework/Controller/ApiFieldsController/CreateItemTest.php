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

class CreateItemTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        $item = $this->doctrine->getRepository(ListItem::class)->findOneBy(['value' => 4]);
        self::assertNull($item);

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        $item = $this->doctrine->getRepository(ListItem::class)->findOneBy(['value' => 4]);
        self::assertNotNull($item);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isRedirect("http://localhost/api/items/{$item->id}"));
    }

    public function test400()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $data = [
            'value' => 4,
            'text'  => 'typo',
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s/items', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'value' => 3,
            'text'  => 'typo',
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s/items', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
