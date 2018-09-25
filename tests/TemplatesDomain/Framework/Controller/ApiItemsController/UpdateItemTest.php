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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiItemsController;

use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateItemTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $data = [
            'value' => $item->value,
            'text'  => 'medium',
        ];

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($item);

        self::assertSame('medium', $item->text);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var ListItem $item */
        [/* skipping */, $item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $data = [
            'value' => $item->value,
            'text'  => 'medium',
        ];

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $data = [
            'value' => $item->value,
            'text'  => 'medium',
        ];

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $data = [
            'value' => $item->value,
            'text'  => 'medium',
        ];

        $uri = sprintf('/api/items/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [/* skipping */, $item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $data = [
            'value' => $item->value,
            'text'  => 'high',
        ];

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
