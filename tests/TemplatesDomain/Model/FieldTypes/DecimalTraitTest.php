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

        $this->object = new Field($state, FieldType::DECIMAL);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testValidationConstraints()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $repository */
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        $this->object->name = 'Custom field';
        $this->object->asDecimal($repository)
            ->setMinimumValue('0')
            ->setMaximumValue('100');

        $errors = $this->validator->validate('0', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('100', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('0.0000000000', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('100.0000000000', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('-0.000000001', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('100.0000000001', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('\'Custom field\' should be in range from 0 to 100.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('test', $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->object->asDecimal($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
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
