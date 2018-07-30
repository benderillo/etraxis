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

        $this->object = new Field($state, FieldType::STRING);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testValidationConstraints()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        $this->object->asString($repository)->setMaximumLength(12);
        $this->object->asString($repository)->getPCRE()->check = '(\d{3})-(\d{3})-(\d{4})';

        $errors = $this->validator->validate('123-456-7890', $this->object->asString($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate('123-456-78901', $this->object->asString($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is too long. It should have 12 characters or less.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('123 456 7890', $this->object->asString($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->object->asString($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->object->asString($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
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
