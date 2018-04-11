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

namespace eTraxis\TemplatesDomain\Application\Command\Templates;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateTemplateCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        $template = $repository->findOneBy(['name' => 'Bugfix']);
        self::assertNull($template);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $result = $this->commandbus->handle($command);

        /** @var Template $template */
        $template = $repository->findOneBy(['name' => 'Bugfix']);
        self::assertInstanceOf(Template::class, $template);
        self::assertSame($result, $template);

        self::assertSame($project, $template->project);
        self::assertSame('Bugfix', $template->name);
        self::assertSame('bug', $template->prefix);
        self::assertSame('Error reports', $template->description);
        self::assertSame(5, $template->criticalAge);
        self::assertSame(10, $template->frozenTime);
        self::assertTrue($template->isLocked);
    }

    public function testUnknownProject()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown project.');

        $this->loginAs('admin@example.com');

        $command = new CreateTemplateCommand([
            'project'     => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Development',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);
    }

    public function testPrefixConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with specified prefix already exists.');

        $this->loginAs('admin@example.com');

        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $command = new CreateTemplateCommand([
            'project'     => $project->id,
            'name'        => 'Bugfix',
            'prefix'      => 'task',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);
    }
}