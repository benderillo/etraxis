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

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;

class ListTraitTest extends TransactionalTestCase
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

        /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository $repository */
        $repository = $this->doctrine->getRepository(Field::class);

        [$this->object] = $repository->findBy([
            'name' => 'Priority',
        ]);
    }

    public function testValidationConstraints()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        $errors = $this->validator->validate(1, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(3, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);

        $errors = $this->validator->validate(0, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should be greater than 0.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(4, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('The value you selected is not a valid choice.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(-1, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate(12.34, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $errors = $this->validator->validate('test', $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value is not valid.', $errors->get(0)->getMessage());

        $this->object->isRequired = true;

        $errors = $this->validator->validate(null, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertNotCount(0, $errors);
        self::assertSame('This value should not be blank.', $errors->get(0)->getMessage());

        $this->object->isRequired = false;

        $errors = $this->validator->validate(null, $this->object->asList($repository)->getValidationConstraints($this->translator));
        self::assertCount(0, $errors);
    }

    public function testDefaultValue()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository $fieldRepository */
        $fieldRepository = $this->doctrine->getRepository(Field::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $itemRepository */
        $itemRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field[] $fields */
        $fields = $fieldRepository->findBy([
            'name' => 'Priority',
        ]);

        /** @var ListItem $item1 */
        $item1 = $itemRepository->findOneBy([
            'field' => $fields[0],
            'value' => 1,
        ]);

        /** @var ListItem $item2 */
        $item2 = $itemRepository->findOneBy([
            'field' => $fields[1],
            'value' => 2,
        ]);

        $field      = $fields[0]->asList($itemRepository);
        $parameters = $this->getProperty($fields[0], 'parameters');

        $field->setDefaultValue($item1);
        self::assertSame($item1, $field->getDefaultValue());
        self::assertSame($item1->id, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue($item2);
        self::assertSame($item1, $field->getDefaultValue());
        self::assertSame($item1->id, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue(null);
        self::assertNull($field->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
