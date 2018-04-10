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

use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateTemplateCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'id'          => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);

        /** @var Template $template */
        $template = $repository->find($template->id);

        self::assertSame('Bugfix', $template->name);
        self::assertSame('bug', $template->prefix);
        self::assertSame('Error reports', $template->description);
        self::assertSame(5, $template->criticalAge);
        self::assertSame(10, $template->frozenTime);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'id'          => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'bug',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateTemplateCommand([
            'id'          => self::UNKNOWN_ENTITY_ID,
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

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'id'          => $template->id,
            'name'        => 'Support',
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

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UpdateTemplateCommand([
            'id'          => $template->id,
            'name'        => 'Bugfix',
            'prefix'      => 'issue',
            'description' => 'Error reports',
            'criticalAge' => 5,
            'frozenTime'  => 10,
        ]);

        $this->commandbus->handle($command);
    }
}
