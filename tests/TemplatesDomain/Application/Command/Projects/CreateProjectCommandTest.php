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

namespace eTraxis\TemplatesDomain\Application\Command\Projects;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Projects\CreateProjectHandler::handle
 */
class CreateProjectCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Awesome Express']);
        self::assertNull($project);

        $command = new CreateProjectCommand([
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Awesome Express']);
        self::assertInstanceOf(Project::class, $project);
        self::assertSame($result, $project);

        self::assertSame('Awesome Express', $project->name);
        self::assertSame('Newspaper-delivery company', $project->description);
        self::assertTrue($project->isSuspended);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        $command = new CreateProjectCommand([
            'name'        => 'Awesome Express',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ]);

        $this->commandBus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Project with specified name already exists.');

        $this->loginAs('admin@example.com');

        $command = new CreateProjectCommand([
            'name'        => 'Distinctio',
            'description' => 'Newspaper-delivery company',
            'suspended'   => true,
        ]);

        $this->commandBus->handle($command);
    }
}
