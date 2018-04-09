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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateGroupCommandTest extends TransactionalTestCase
{
    public function testLocalSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand([
            'id'          => $group->id,
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ]);

        $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $repository->find($group->id);

        self::assertSame('Programmers', $group->name);
        self::assertSame('Software Engineers', $group->description);
    }

    public function testGlobalSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        $group = $repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand([
            'id'          => $group->id,
            'name'        => 'All my slaves',
            'description' => 'Human beings',
        ]);

        $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $repository->find($group->id);

        self::assertSame('All my slaves', $group->name);
        self::assertSame('Human beings', $group->description);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand([
            'id'          => $group->id,
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownGroup()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateGroupCommand([
            'id'          => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testLocalGroupConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand([
            'id'   => $group->id,
            'name' => 'Company Staff',
        ]);

        try {
            $this->commandbus->handle($command);
        }
        catch (ConflictHttpException $exception) {
            $this->fail($exception->getMessage());
        }

        $command = new UpdateGroupCommand([
            'id'   => $group->id,
            'name' => 'Managers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testGlobalGroupConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        /** @var Group $group */
        $group = $repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand([
            'id'   => $group->id,
            'name' => 'Managers',
        ]);

        try {
            $this->commandbus->handle($command);
        }
        catch (ConflictHttpException $exception) {
            $this->fail($exception->getMessage());
        }

        $command = new UpdateGroupCommand([
            'id'   => $group->id,
            'name' => 'Company Clients',
        ]);

        $this->commandbus->handle($command);
    }
}
