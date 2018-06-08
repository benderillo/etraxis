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
    /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testLocalSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand([
            'group'       => $group->id,
            'name'        => 'Programmers',
            'description' => 'Software Engineers',
        ]);

        $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->id);

        self::assertSame('Programmers', $group->name);
        self::assertSame('Software Engineers', $group->description);
    }

    public function testGlobalSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand([
            'group'       => $group->id,
            'name'        => 'All my slaves',
            'description' => 'Human beings',
        ]);

        $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->id);

        self::assertSame('All my slaves', $group->name);
        self::assertSame('Human beings', $group->description);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand([
            'group'       => $group->id,
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
            'group'       => self::UNKNOWN_ENTITY_ID,
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

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new UpdateGroupCommand([
            'group' => $group->id,
            'name'  => 'Company Staff',
        ]);

        try {
            $this->commandbus->handle($command);
        }
        catch (ConflictHttpException $exception) {
            self::fail($exception->getMessage());
        }

        $command = new UpdateGroupCommand([
            'group' => $group->id,
            'name'  => 'Managers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testGlobalGroupConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Company Staff']);

        $command = new UpdateGroupCommand([
            'group' => $group->id,
            'name'  => 'Managers',
        ]);

        try {
            $this->commandbus->handle($command);
        }
        catch (ConflictHttpException $exception) {
            self::fail($exception->getMessage());
        }

        $command = new UpdateGroupCommand([
            'group' => $group->id,
            'name'  => 'Company Clients',
        ]);

        $this->commandbus->handle($command);
    }
}
