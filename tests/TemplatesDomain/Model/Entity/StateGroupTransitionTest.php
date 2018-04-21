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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class StateGroupTransitionTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 3);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 4);

        $group = new Group($project);
        $this->setProperty($group, 'id', 5);

        $transition = new StateGroupTransition($from, $to, $group);
        self::assertSame($from, $this->getProperty($transition, 'fromState'));
        self::assertSame($to, $this->getProperty($transition, 'toState'));
        self::assertSame($group, $this->getProperty($transition, 'group'));
    }

    public function testConstructorExceptionStates()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('States must belong the same template');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template1 = new Template($project);
        $this->setProperty($template1, 'id', 2);

        $template2 = new Template($project);
        $this->setProperty($template2, 'id', 3);

        $from = new State($template1, StateType::INITIAL);
        $this->setProperty($from, 'id', 4);

        $to = new State($template2, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 5);

        $group = new Group($project);
        $this->setProperty($group, 'id', 6);

        new StateGroupTransition($from, $to, $group);
    }

    public function testConstructorExceptionGroup()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: foo');

        $project1 = new Project();
        $this->setProperty($project1, 'id', 1);

        $project2 = new Project();
        $this->setProperty($project2, 'id', 2);

        $template = new Template($project1);
        $this->setProperty($template, 'id', 3);

        $from = new State($template, StateType::INITIAL);
        $this->setProperty($from, 'id', 4);

        $to = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($to, 'id', 5);

        $group = new Group($project2);
        $this->setProperty($group, 'id', 6);
        $group->name = 'foo';

        new StateGroupTransition($from, $to, $group);
    }
}