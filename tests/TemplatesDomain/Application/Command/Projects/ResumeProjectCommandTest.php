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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResumeProjectCommandTest extends TransactionalTestCase
{
    public function testResumeProject()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        /** @var Project $project */
        $project = $repository->findOneBy(['name' => 'Distinctio']);

        self::assertTrue($project->isSuspended);

        $command = new ResumeProjectCommand([
            'id' => $project->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($project);
        self::assertFalse($project->isSuspended);
    }

    public function testIdempotence()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        /** @var Project $project */
        $project = $repository->findOneBy(['name' => 'Molestiae']);

        self::assertFalse($project->isSuspended);

        $command = new ResumeProjectCommand([
            'id' => $project->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($project);
        self::assertFalse($project->isSuspended);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        /** @var Project $project */
        $project = $repository->findOneBy(['name' => 'Distinctio']);

        $command = new ResumeProjectCommand([
            'id' => $project->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownProject()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new ResumeProjectCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);
    }
}
