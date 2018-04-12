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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StateGroupTransition;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetGroupsTransitionCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $before = [
            'Managers',
            'Support Engineers',
        ];

        $after = [
            'Developers',
            'Support Engineers',
        ];

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame($before, $this->transitionsToArray($fromState->groupTransitions, $toState));

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($fromState);
        self::assertSame($after, $this->transitionsToArray($fromState->groupTransitions, $toState));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'DESC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'DESC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'DESC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
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

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownFromState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => self::UNKNOWN_ENTITY_ID,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownToState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => self::UNKNOWN_ENTITY_ID,
            'groups' => [
                $developers->id,
                $support->id,
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
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'DESC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testWrongGroup()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: Developers');

        $this->loginAs('admin@example.com');

        /** @var State $fromState */
        [$fromState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Submitted'], ['id' => 'ASC']);

        /** @var State $toState */
        [$toState] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetGroupsTransitionCommand([
            'from'   => $fromState->id,
            'to'     => $toState->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    /**
     * @param StateGroupTransition[] $transitions
     * @param State                  $state
     *
     * @return string[]
     */
    protected function transitionsToArray(array $transitions, State $state): array
    {
        $filtered = array_filter($transitions, function (StateGroupTransition $transition) use ($state) {
            return $transition->toState === $state;
        });

        $result = array_map(function (StateGroupTransition $transition) {
            return $transition->group->name;
        }, $filtered);

        sort($result);

        return $result;
    }
}
