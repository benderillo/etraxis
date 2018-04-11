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

        $state = new State($template, 'Unknown');
        self::assertSame(StateType::INTERMEDIATE, $state->type);
    }

    public function testResponsible()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $state->responsible = StateResponsible::ASSIGN;
        self::assertSame(StateResponsible::ASSIGN, $state->responsible);
    }

    public function testResponsibleException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown responsibility type: bar');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $state->responsible = 'bar';
    }
}
