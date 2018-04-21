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

namespace eTraxis\TemplatesDomain\Application\Command\ListItems;

use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteListItemCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);
        self::assertNotNull($item);

        $command = new DeleteListItemCommand([
            'item' => $item->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $item = $this->repository->find($command->item);
        self::assertNull($item);
        }

    public function testUnknownItem()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteListItemCommand([
            'item' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $command = new DeleteListItemCommand([
            'item' => $item->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var ListItem $item */
        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'DESC']);

        $command = new DeleteListItemCommand([
            'item' => $item->id,
        ]);

        $this->commandbus->handle($command);
    }
}
