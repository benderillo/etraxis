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

class ListItemTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        $this->setProperty($field, 'id', 2);

        $item = new ListItem($field);
        self::assertSame($field, $item->field);
    }

    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid field type: number');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::NUMBER);
        $this->setProperty($field, 'id', 2);

        new ListItem($field);
    }

    public function testJsonSerialize()
    {
        $expected = [
            'id'    => 3,
            'value' => 12,
            'text'  => 'December',
        ];

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        $this->setProperty($field, 'id', 2);

        $item = new ListItem($field);
        $this->setProperty($item, 'id', 3);

        $item->value = 12;
        $item->text  = 'December';

        self::assertSame($expected, $item->jsonSerialize());
    }
}
