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

class GetItemTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $expected = [
            'id'    => $item->id,
            'value' => 2,
            'text'  => 'normal',
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'ASC']);

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/items/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
