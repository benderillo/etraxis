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

namespace eTraxis\TemplatesDomain\Model\Entity;

use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $template = new Template(new Project());
        $this->setProperty($template, 'id', 1);

        $state = new State($template, StateType::INITIAL);
        self::assertSame($template, $state->template);
        self::assertSame(StateType::INITIAL, $state->type);
    }

    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown state type: foo');

        $template = new Template(new Project());
        $this->setProperty($template, 'id', 1);

        new State($template, 'foo');
    }

    public function testResponsible()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $state->responsible = StateResponsible::ASSIGN;
        self::assertSame(StateResponsible::ASSIGN, $state->responsible);
    }

    public function testResponsibleFinal()
    {
        $state = new State(new Template(new Project()), StateType::FINAL);

        $state->responsible = StateResponsible::ASSIGN;
        self::assertSame(StateResponsible::REMOVE, $state->responsible);
    }

    public function testResponsibleException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown responsibility type: bar');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $state->responsible = 'bar';
    }

    public function testNextState()
    {
        $template = new Template(new Project());
        $this->setProperty($template, 'id', 1);

        $nextState = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($nextState, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        self::assertNull($state->nextState);

        $state->nextState = $nextState;
        self::assertSame($nextState, $state->nextState);

        $state->nextState = null;
        self::assertNull($state->nextState);
    }

    public function testNextStateFinal()
    {
        $template = new Template(new Project());
        $this->setProperty($template, 'id', 1);

        $nextState = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($nextState, 'id', 2);

        $state = new State($template, StateType::FINAL);
        self::assertNull($state->nextState);

        $state->nextState = $nextState;
        self::assertNull($state->nextState);
    }

    public function testNextStateException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown state: alien');

        $template1 = new Template(new Project());
        $this->setProperty($template1, 'id', 1);

        $template2 = new Template(new Project());
        $this->setProperty($template2, 'id', 2);

        $nextState = new State($template1, StateType::INTERMEDIATE);
        $this->setProperty($nextState, 'name', 'alien');

        $state = new State($template2, StateType::INTERMEDIATE);

        $state->nextState = $nextState;
    }

    public function testRolePermissions()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        self::assertSame([], $state->roleTransitions);

        /** @var \Doctrine\Common\Collections\ArrayCollection $transitions */
        $transitions = $this->getProperty($state, 'roleTransitionsCollection');
        $transitions->add('Role transition A');
        $transitions->add('Role transition B');

        self::assertSame(['Role transition A', 'Role transition B'], $state->roleTransitions);
    }

    public function testGroupPermissions()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        self::assertSame([], $state->groupTransitions);

        /** @var \Doctrine\Common\Collections\ArrayCollection $transitions */
        $transitions = $this->getProperty($state, 'groupTransitionsCollection');
        $transitions->add('Group transition A');
        $transitions->add('Group transition B');

        self::assertSame(['Group transition A', 'Group transition B'], $state->groupTransitions);
    }

    public function testResponsibleGroups()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        self::assertSame([], $state->responsibleGroups);

        /** @var \Doctrine\Common\Collections\ArrayCollection $groups */
        $groups = $this->getProperty($state, 'responsibleGroupsCollection');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $state->responsibleGroups);
    }
}
