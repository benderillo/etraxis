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

class DeleteTemplateCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);
        self::assertNotNull($template);

        $command = new DeleteTemplateCommand([
            'id' => $template->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $template = $repository->find($command->id);
        self::assertNull($template);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteTemplateCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        /** @var Template $template */
        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $command = new DeleteTemplateCommand([
            'id' => $template->id,
        ]);

        $this->commandbus->handle($command);
    }
}
