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

class DateTraitTest extends WebTestCase
{
    use ReflectionTrait;

    protected const SECS_IN_DAY = 86400;

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

        $this->object = new Field($state, FieldType::DATE);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testValidationConstraints()
    {
        $this->object->name = 'Custom field';
        $this->object->asDate()
            ->setMinimumValue(0)
            ->setMaximumValue(7);

        $now = time();

        $errors = $this->validator->validate(date('Y-m-d', $now), $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(date('Y-m-d', $now + self::SECS_IN_DAY * 7), $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(date('Y-m-d', $now - self::SECS_IN_DAY), $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame(sprintf('\'Custom field\' should be in range from %s to %s.', date('n/j/y', $now), date('n/j/y', $now + self::SECS_IN_DAY * 7)), $errors->get(0)->getMessage());

        $errors = $this->validator->validate(date('Y-m-d', $now + self::SECS_IN_DAY * 8), $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame(sprintf('\'Custom field\' should be in range from %s to %s.', date('n/j/y', $now), date('n/j/y', $now + self::SECS_IN_DAY * 7)), $errors->get(0)->getMessage());

        $errors = $this->validator->validate('2015-22-11', $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->object->asDate()->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
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
