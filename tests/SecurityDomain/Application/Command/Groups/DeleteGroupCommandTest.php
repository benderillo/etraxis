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
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);
        self::assertNotNull($group);

        $command = new DeleteGroupCommand([
            'id' => $group->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $group = $repository->find($command->id);
        self::assertNull($group);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteGroupCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new DeleteGroupCommand([
            'id' => $group->id,
        ]);

        $this->commandbus->handle($command);
    }
}
