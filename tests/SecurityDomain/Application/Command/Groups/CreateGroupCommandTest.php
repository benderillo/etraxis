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
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateGroupCommandTest extends TransactionalTestCase
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

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertNull($group);

        $command = new CreateGroupCommand([
            'project'     => $project->id,
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ]);

        $result = $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertInstanceOf(Group::class, $group);
        self::assertSame($result, $group);

        self::assertSame($project, $group->project);
        self::assertSame('Testers', $group->name);
        self::assertSame('Test Engineers', $group->description);
    }

    public function testGlobalSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertNull($group);

        $command = new CreateGroupCommand([
            'name'        => 'Testers',
            'description' => 'Test Engineers',
        ]);

        $result = $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $this->repository->findOneBy(['name' => 'Testers']);
        self::assertInstanceOf(Group::class, $group);
        self::assertSame($result, $group);

        self::assertNull($group->project);
        self::assertSame('Testers', $group->name);
        self::assertSame('Test Engineers', $group->description);
    }

    public function testUnknownProject()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginAs('admin@example.com');

        $command = new CreateGroupCommand([
            'project' => self::UNKNOWN_ENTITY_ID,
            'name'    => 'Testers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $command = new CreateGroupCommand([
            'name' => 'Testers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testLocalGroupConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateGroupCommand([
            'project' => $project->id,
            'name'    => 'Company Staff',
        ]);

        try {
            $this->commandbus->handle($command);
        }
        catch (ConflictHttpException $exception) {
            $this->fail($exception->getMessage());
        }

        $command = new CreateGroupCommand([
            'project' => $project->id,
            'name'    => 'Developers',
        ]);

        $this->commandbus->handle($command);
    }

    public function testGlobalGroupConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Group with specified name already exists.');

        $this->loginAs('admin@example.com');

        $command = new CreateGroupCommand([
            'name' => 'Developers',
        ]);

        try {
            $this->commandbus->handle($command);
        }
        catch (ConflictHttpException $exception) {
            $this->fail($exception->getMessage());
        }

        $command = new CreateGroupCommand([
            'name' => 'Company Staff',
        ]);

        $this->commandbus->handle($command);
    }
}
