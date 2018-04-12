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

namespace eTraxis\TemplatesDomain\Application\Command\States;

use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteStateCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);
        self::assertNotNull($state);

        $command = new DeleteStateCommand([
            'state' => $state->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $state = $this->repository->find($command->state);
        self::assertNull($state);
    }

    public function testUnknown()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteStateCommand([
            'state' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        $command = new DeleteStateCommand([
            'state' => $state->id,
        ]);

        $this->commandbus->handle($command);
    }
}
