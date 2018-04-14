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

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        self::assertSame($state, $this->getProperty($field, 'state'));
        self::assertSame(FieldType::LIST, $this->getProperty($field, 'type'));
    }

    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field type: foo');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        new Field($state, 'foo');
    }
}
