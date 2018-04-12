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

use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StateRoleTransition;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetRolesTransitionCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $before = [
            SystemRole::AUTHOR,
            SystemRole::RESPONSIBLE,
        ];

        $after = [
            SystemRole::ANYONE,
            SystemRole::RESPONSIBLE,
        ];

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        self::assertSame($before, $this->transitionsToArray($fromState->roleTransitions, $toState));

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($fromState);
        self::assertSame($after, $this->transitionsToArray($fromState->roleTransitions, $toState));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'DESC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'DESC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testFinalState()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownFromState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => self::UNKNOWN_ENTITY_ID,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownToState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => self::UNKNOWN_ENTITY_ID,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testWrongStates()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('States must belong the same template');

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'DESC']);

        $command = new SetRolesTransitionCommand([
            'from'  => $fromState->id,
            'to'    => $toState->id,
            'roles' => [
                SystemRole::ANYONE,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    /**
     * @param StateRoleTransition[] $transitions
     * @param State                 $state
     *
     * @return string[]
     */
    protected function transitionsToArray(array $transitions, State $state): array
    {
        $filtered = array_filter($transitions, function (StateRoleTransition $transition) use ($state) {
            return $transition->toState === $state;
        });

        $result = array_map(function (StateRoleTransition $transition) {
            return $transition->role;
        }, $filtered);

        sort($result);

        return $result;
    }
}
