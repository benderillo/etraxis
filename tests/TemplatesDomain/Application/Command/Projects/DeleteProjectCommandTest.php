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

/**
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Projects\DeleteProjectHandler::handle
 */
class DeleteProjectCommandTest extends TransactionalTestCase
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
        $project = $this->repository->findOneBy(['name' => 'Presto']);
        self::assertNotNull($project);

        $command = new DeleteProjectCommand([
            'project' => $project->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $project = $this->repository->findOneBy(['name' => 'Presto']);
        self::assertNull($project);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteProjectCommand([
            'project' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Project $project */
        $project = $this->repository->findOneBy(['name' => 'Presto']);

        $command = new DeleteProjectCommand([
            'project' => $project->id,
        ]);

        $this->commandBus->handle($command);
    }
}
