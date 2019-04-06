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

/**
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Templates\DeleteTemplateHandler::handle
 */
class DeleteTemplateCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);
        self::assertNotNull($template);

        $command = new DeleteTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->clear();

        $template = $this->repository->find($command->template);
        self::assertNull($template);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteTemplateCommand([
            'template' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $command = new DeleteTemplateCommand([
            'template' => $template->id,
        ]);

        $this->commandBus->handle($command);
    }
}
