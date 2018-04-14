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

namespace eTraxis\TemplatesDomain\Model\FieldTypes;

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class DateTraitTest extends TestCase
{
    use ReflectionTrait;

    /** @var Field */
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::DATE);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testMinimumValue()
    {
        $field      = $this->object->asDate();
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(DateInterface::MIN_VALUE, DateInterface::MAX_VALUE);
        $min   = DateInterface::MIN_VALUE - 1;
        $max   = DateInterface::MAX_VALUE + 1;

        $field->setMinimumValue($value);
        self::assertSame($value, $field->getMinimumValue());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $field->setMinimumValue($min);
        self::assertSame(DateInterface::MIN_VALUE, $field->getMinimumValue());

        $field->setMinimumValue($max);
        self::assertSame(DateInterface::MAX_VALUE, $field->getMinimumValue());
    }

    public function testMaximumValue()
    {
        $field      = $this->object->asDate();
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(DateInterface::MIN_VALUE, DateInterface::MAX_VALUE);
        $min   = DateInterface::MIN_VALUE - 1;
        $max   = DateInterface::MAX_VALUE + 1;

        $field->setMaximumValue($value);
        self::assertSame($value, $field->getMaximumValue());
        self::assertSame($value, $this->getProperty($parameters, 'parameter2'));

        $field->setMaximumValue($min);
        self::assertSame(DateInterface::MIN_VALUE, $field->getMaximumValue());

        $field->setMaximumValue($max);
        self::assertSame(DateInterface::MAX_VALUE, $field->getMaximumValue());
    }

    public function testDefaultValue()
    {
        $field      = $this->object->asDate();
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(DateInterface::MIN_VALUE, DateInterface::MAX_VALUE);
        $min   = DateInterface::MIN_VALUE - 1;
        $max   = DateInterface::MAX_VALUE + 1;

        $field->setDefaultValue($value);
        self::assertSame($value, $field->getDefaultValue());
        self::assertSame($value, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue($min);
        self::assertSame(DateInterface::MIN_VALUE, $field->getDefaultValue());

        $field->setDefaultValue($max);
        self::assertSame(DateInterface::MAX_VALUE, $field->getDefaultValue());

        $field->setDefaultValue(null);
        self::assertNull($field->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
