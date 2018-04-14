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
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;

class DecimalTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var Field */
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::DECIMAL);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testMinimumValue()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $repository */
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        $field      = $this->object->asDecimal($repository);
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $field->setMinimumValue($value);
        self::assertSame($value, $field->getMinimumValue());
        self::assertNotNull($this->getProperty($parameters, 'parameter1'));

        $field->setMinimumValue($min);
        self::assertSame(DecimalInterface::MIN_VALUE, $field->getMinimumValue());

        $field->setMinimumValue($max);
        self::assertSame(DecimalInterface::MAX_VALUE, $field->getMinimumValue());
    }

    public function testMaximumValue()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $repository */
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        $field      = $this->object->asDecimal($repository);
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $field->setMaximumValue($value);
        self::assertSame($value, $field->getMaximumValue());
        self::assertNotNull($this->getProperty($parameters, 'parameter2'));

        $field->setMaximumValue($min);
        self::assertSame(DecimalInterface::MIN_VALUE, $field->getMaximumValue());

        $field->setMaximumValue($max);
        self::assertSame(DecimalInterface::MAX_VALUE, $field->getMaximumValue());
    }

    public function testDefaultValue()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $repository */
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        $field      = $this->object->asDecimal($repository);
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = '3.14159292';
        $min   = '-10000000000.00';
        $max   = '10000000000.00';

        $field->setDefaultValue($value);
        self::assertSame($value, $field->getDefaultValue());
        self::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue($min);
        self::assertSame(DecimalInterface::MIN_VALUE, $field->getDefaultValue());

        $field->setDefaultValue($max);
        self::assertSame(DecimalInterface::MAX_VALUE, $field->getDefaultValue());

        $field->setDefaultValue(null);
        self::assertNull($field->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
