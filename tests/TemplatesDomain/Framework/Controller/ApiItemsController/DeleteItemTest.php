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

class DeleteItemTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'DESC']);
        self::assertNotNull($item);

        $id = $item->id;

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNull($this->doctrine->getRepository(ListItem::class)->find($id));
    }

    public function test401()
    {
        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'DESC']);

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['value' => 2], ['id' => 'DESC']);

        $uri = sprintf('/api/items/%s', $item->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
