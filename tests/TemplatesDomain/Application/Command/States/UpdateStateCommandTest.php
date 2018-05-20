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

use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateStateCommandTest extends TransactionalTestCase
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

        /** @var State $nextState */
        [$nextState] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);
        self::assertNull($state->nextState);

        $command = new UpdateStateCommand([
            'state'       => $state->id,
            'name'        => 'Forwarded',
            'responsible' => StateResponsible::KEEP,
            'nextState'   => $nextState->id,
        ]);

        $this->commandbus->handle($command);

        /** @var State $state */
        $state = $this->repository->find($state->id);

        self::assertSame('Forwarded', $state->name);
        self::assertSame(StateResponsible::KEEP, $state->responsible);
        self::assertSame($nextState, $state->nextState);
    }

    public function testUnknownNextState()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown next state.');

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand([
            'state'       => $state->id,
            'name'        => 'Forwarded',
            'responsible' => StateResponsible::KEEP,
            'nextState'   => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);
    }

    public function testWrongNextState()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown next state.');

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $nextState */
        [$nextState] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'DESC']);

        $command = new UpdateStateCommand([
            'state'       => $state->id,
            'name'        => 'Forwarded',
            'responsible' => StateResponsible::KEEP,
            'nextState'   => $nextState->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand([
            'state'       => $state->id,
            'name'        => 'Forwarded',
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */,  /* skipping */, $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand([
            'state'       => $state->id,
            'name'        => 'Forwarded',
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new UpdateStateCommand([
            'state'       => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Forwarded',
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandbus->handle($command);
    }

    public function testNameConflict()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('State with specified name already exists.');

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new UpdateStateCommand([
            'state'       => $state->id,
            'name'        => 'Completed',
            'responsible' => StateResponsible::KEEP,
        ]);

        $this->commandbus->handle($command);
    }
}
