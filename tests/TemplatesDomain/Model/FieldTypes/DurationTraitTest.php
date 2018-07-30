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
use eTraxis\Tests\WebTestCase;

class DurationTraitTest extends WebTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Symfony\Component\Validator\Validator\ValidatorInterface */
    protected $validator;

    /** @var Field */
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->validator  = $this->client->getContainer()->get('validator');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::DURATION);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testValidationConstraints()
    {
        $this->object->name = 'Custom field';
        $this->object->asDuration()
            ->setMinimumValue('0:00')
            ->setMaximumValue('24:00');

        $errors = $this->validator->validate('0:00', $this->object->asDuration()->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('24:00', $this->object->asDuration()->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('24:01', $this->object->asDuration()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0:00 to 24:00.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('0:60', $this->object->asDuration()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->object->asDuration()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->object->asDuration()->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }

    public function testMinimumValue()
    {
        $field      = $this->object->asDuration();
        $parameters = $this->getProperty($this->object, 'parameters');

        $duration = 866;
        $value    = '14:26';
        $min      = '0:00';
        $max      = '999999:59';

        $field->setMinimumValue($value);
        self::assertSame($value, $field->getMinimumValue());
        self::assertSame($duration, $this->getProperty($parameters, 'parameter1'));

        $field->setMinimumValue($min);
        self::assertSame($min, $field->getMinimumValue());

        $field->setMinimumValue($max);
        self::assertSame($max, $field->getMinimumValue());
    }

    public function testMaximumValue()
    {
        $field      = $this->object->asDuration();
        $parameters = $this->getProperty($this->object, 'parameters');

        $duration = 866;
        $value    = '14:26';
        $min      = '0:00';
        $max      = '999999:59';

        $field->setMaximumValue($value);
        self::assertSame($value, $field->getMaximumValue());
        self::assertSame($duration, $this->getProperty($parameters, 'parameter2'));

        $field->setMaximumValue($min);
        self::assertSame($min, $field->getMaximumValue());

        $field->setMaximumValue($max);
        self::assertSame($max, $field->getMaximumValue());
    }

    public function testDefaultValue()
    {
        $field      = $this->object->asDuration();
        $parameters = $this->getProperty($this->object, 'parameters');

        $duration = 866;
        $value    = '14:26';
        $min      = '0:00';
        $max      = '999999:59';

        $field->setDefaultValue($value);
        self::assertSame($value, $field->getDefaultValue());
        self::assertSame($duration, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue($min);
        self::assertSame($min, $field->getDefaultValue());

        $field->setDefaultValue($max);
        self::assertSame($max, $field->getDefaultValue());

        $field->setDefaultValue(null);
        self::assertNull($field->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }

    public function testToNumber()
    {
        $field = $this->object->asDuration();

        self::assertNull($field->toNumber(null));
        self::assertNull($field->toNumber('0:99'));
        self::assertSame(866, $field->toNumber('14:26'));
    }

    public function testToString()
    {
        $field = $this->object->asDuration();

        self::assertNull($field->toString(null));
        self::assertSame('0:00', $field->toString(DurationInterface::MIN_VALUE - 1));
        self::assertSame('999999:59', $field->toString(DurationInterface::MAX_VALUE + 1));
        self::assertSame('14:26', $field->toString(866));
    }
}
