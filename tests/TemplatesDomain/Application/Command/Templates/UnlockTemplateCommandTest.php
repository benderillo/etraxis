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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UnlockTemplateCommandTest extends TransactionalTestCase
{
    public function testUnlockTemplate()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        self::assertTrue($template->isLocked);

        $command = new UnlockTemplateCommand([
            'id' => $template->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertFalse($template->isLocked);
    }

    public function testIdempotence()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        self::assertFalse($template->isLocked);

        $command = new UnlockTemplateCommand([
            'id' => $template->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertFalse($template->isLocked);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new UnlockTemplateCommand([
            'id' => $template->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UnlockTemplateCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);
    }
}
