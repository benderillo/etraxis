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

namespace eTraxis\SecurityDomain\Application\Command\Groups;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteGroupCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);
        self::assertNotNull($group);

        $command = new DeleteGroupCommand([
            'group' => $group->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $group = $this->repository->find($command->group);
        self::assertNull($group);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteGroupCommand([
            'group' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new DeleteGroupCommand([
            'group' => $group->id,
        ]);

        $this->commandBus->handle($command);
    }
}
