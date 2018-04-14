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
use eTraxis\TemplatesDomain\Model\Entity\FieldPCRE;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;

class StringTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var Field */
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::STRING);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testMaximumLength()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        $field      = $this->object->asString($repository);
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = random_int(StringInterface::MIN_LENGTH, StringInterface::MAX_LENGTH);
        $min   = StringInterface::MIN_LENGTH - 1;
        $max   = StringInterface::MAX_LENGTH + 1;

        $field->setMaximumLength($value);
        self::assertSame($value, $field->getMaximumLength());
        self::assertSame($value, $this->getProperty($parameters, 'parameter1'));

        $field->setMaximumLength($min);
        self::assertSame(StringInterface::MIN_LENGTH, $field->getMaximumLength());

        $field->setMaximumLength($max);
        self::assertSame(StringInterface::MAX_LENGTH, $field->getMaximumLength());
    }

    public function testDefaultValue()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        $field      = $this->object->asString($repository);
        $parameters = $this->getProperty($this->object, 'parameters');

        $value = 'eTraxis';

        $field->setDefaultValue($value);
        self::assertSame($value, $field->getDefaultValue());
        self::assertNotNull($this->getProperty($parameters, 'defaultValue'));

        $huge = str_pad(null, StringInterface::MAX_LENGTH + 1);
        $trim = str_pad(null, StringInterface::MAX_LENGTH);

        $field->setDefaultValue($huge);
        self::assertSame($trim, $field->getDefaultValue());

        $field->setDefaultValue(null);
        self::assertNull($field->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    public function testPCRE()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        $field = $this->object->asString($repository);

        self::assertInstanceOf(FieldPCRE::class, $field->getPCRE());
    }
}
