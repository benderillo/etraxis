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

use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetInitialStateCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        /** @var State $initial */
        /** @var State $state */
        [$initial] = $repository->findBy(['name' => 'New'], ['id' => 'ASC']);
        [$state]   = $repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        self::assertSame(StateType::INITIAL, $initial->type);
        self::assertNotSame(StateType::INITIAL, $state->type);

        $command = new SetInitialStateCommand([
            'id' => $state->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($initial);
        $this->doctrine->getManager()->refresh($state);

        self::assertNotSame(StateType::INITIAL, $initial->type);
        self::assertSame(StateType::INITIAL, $state->type);
    }

    public function testIdempotence()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertSame(StateType::INITIAL, $state->type);

        $command = new SetInitialStateCommand([
            'id' => $state->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($state);

        self::assertSame(StateType::INITIAL, $state->type);
    }

    public function testUnknownState()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown state.');

        $this->loginAs('admin@example.com');

        $command = new SetInitialStateCommand([
            'id' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $command = new SetInitialStateCommand([
            'id' => $state->id,
        ]);

        $this->commandbus->handle($command);
    }
}
