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

class DeleteProjectCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        /** @var Project $project */
        $project = $repository->findOneBy(['name' => 'Distinctio']);
        self::assertNotNull($project);

        $command = new DeleteProjectCommand([
            'id' => $project->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $project = $repository->findOneBy(['name' => 'Distinctio']);
        self::assertNull($project);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteProjectCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        /** @var Project $project */
        $project = $repository->findOneBy(['name' => 'Distinctio']);

        $command = new DeleteProjectCommand([
            'id' => $project->id,
        ]);

        $this->commandbus->handle($command);
    }
}