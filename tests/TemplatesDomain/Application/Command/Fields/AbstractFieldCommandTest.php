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

namespace eTraxis\TemplatesDomain\Application\Command\Fields;

use eTraxis\TemplatesDomain\Application\Command\Fields as Command;
use eTraxis\TemplatesDomain\Application\CommandHandler\Fields\AbstractFieldHandler;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AbstractFieldCommandTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $fieldRepository;

    /** @var AbstractFieldHandler $handler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->manager = $this->doctrine->getManager();

        $this->fieldRepository = $this->doctrine->getRepository(Field::class);

        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->client->getContainer()->get('translator');

        $this->handler = new DummyFieldHandler($translator, $this->manager);
    }

    public function testCopyAsCheckboxSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\CheckboxInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertFalse($facade->getDefaultValue());

        $command = new Command\UpdateCheckboxFieldCommand([
            'defaultValue' => true,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertTrue($facade->getDefaultValue());
    }

    public function testCopyAsDateSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DateInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(0, $facade->getMinimumValue());
        self::assertSame(14, $facade->getMaximumValue());
        self::assertSame(14, $facade->getDefaultValue());

        $command = new Command\UpdateDateFieldCommand([
            'minimumValue' => '1',
            'maximumValue' => '7',
            'defaultValue' => '3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame(1, $facade->getMinimumValue());
        self::assertSame(7, $facade->getMaximumValue());
        self::assertSame(3, $facade->getDefaultValue());
    }

    public function testCopyAsDateMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        $command = new Command\UpdateDateFieldCommand([
            'minimumValue' => '7',
            'maximumValue' => '1',
            'defaultValue' => '3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsDateDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from 1 to 7.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        $command = new Command\UpdateDateFieldCommand([
            'minimumValue' => '1',
            'maximumValue' => '7',
            'defaultValue' => '0',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsDecimalSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Test coverage'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame('0', $facade->getMinimumValue());
        self::assertSame('100', $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new Command\UpdateDecimalFieldCommand([
            'minimumValue' => '1',
            'maximumValue' => '10',
            'defaultValue' => '5',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame('1', $facade->getMinimumValue());
        self::assertSame('10', $facade->getMaximumValue());
        self::assertSame('5', $facade->getDefaultValue());
    }

    public function testCopyAsDecimalMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Test coverage'], ['id' => 'ASC']);

        $command = new Command\UpdateDecimalFieldCommand([
            'minimumValue' => '10',
            'maximumValue' => '1',
            'defaultValue' => '5',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsDecimalDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from 1 to 10.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Test coverage'], ['id' => 'ASC']);

        $command = new Command\UpdateDecimalFieldCommand([
            'minimumValue' => '1',
            'maximumValue' => '10',
            'defaultValue' => '0',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsDurationSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame('0:00', $facade->getMinimumValue());
        self::assertSame('999999:59', $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new Command\UpdateDurationFieldCommand([
            'minimumValue' => '0:01',
            'maximumValue' => '0:59',
            'defaultValue' => '0:30',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame('0:01', $facade->getMinimumValue());
        self::assertSame('0:59', $facade->getMaximumValue());
        self::assertSame('0:30', $facade->getDefaultValue());
    }

    public function testCopyAsDurationMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new Command\UpdateDurationFieldCommand([
            'minimumValue' => '0:59',
            'maximumValue' => '0:01',
            'defaultValue' => '0:30',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsDurationDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from 0:01 to 0:59.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new Command\UpdateDurationFieldCommand([
            'minimumValue' => '0:01',
            'maximumValue' => '0:59',
            'defaultValue' => '0:00',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsIssueSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertTrue(true);
    }

    public function testCopyAsListSuccess()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($this->manager);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 2], ['id' => 'ASC']);

        self::assertNull($facade->getDefaultValue());

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame($item, $facade->getDefaultValue());

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => null,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertNull($facade->getDefaultValue());
    }

    public function testCopyAsListUnknownItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsListWrongItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 2], ['id' => 'DESC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsNumberSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(0, $facade->getMinimumValue());
        self::assertSame(1000000000, $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new Command\UpdateNumberFieldCommand([
            'minimumValue' => -100000,
            'maximumValue' => +100000,
            'defaultValue' => 100,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame(-100000, $facade->getMinimumValue());
        self::assertSame(100000, $facade->getMaximumValue());
        self::assertSame(100, $facade->getDefaultValue());
    }

    public function testCopyAsNumberMinMaxValuesError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Maximum value should not be less then minimum one.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new Command\UpdateNumberFieldCommand([
            'minimumValue' => +100000,
            'maximumValue' => -100000,
            'defaultValue' => 100,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsNumberDefaultValueRangeError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should be in range from -100000 to 100000.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new Command\UpdateNumberFieldCommand([
            'minimumValue' => -100000,
            'maximumValue' => +100000,
            'defaultValue' => 100001,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsStringSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\StringInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(40, $facade->getMaximumLength());
        self::assertNull($facade->getDefaultValue());
        self::assertNull($facade->getPCRE()->check);
        self::assertNull($facade->getPCRE()->search);
        self::assertNull($facade->getPCRE()->replace);

        $command = new Command\UpdateStringFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame(20, $facade->getMaximumLength());
        self::assertSame('123-456-7890', $facade->getDefaultValue());
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->check);
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->search);
        self::assertSame('($1) $2-$3', $facade->getPCRE()->replace);
    }

    public function testCopyAsStringDefaultValueLengthError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should not be longer than 10 characters.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        $command = new Command\UpdateStringFieldCommand([
            'maximumLength' => 10,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsStringDefaultValueFormatError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid format of the default value.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        $command = new Command\UpdateStringFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '1234567890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsTextSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(TextValue::MAX_VALUE, $facade->getMaximumLength());
        self::assertNull($facade->getDefaultValue());
        self::assertNull($facade->getPCRE()->check);
        self::assertNull($facade->getPCRE()->search);
        self::assertNull($facade->getPCRE()->replace);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);

        self::assertSame(20, $facade->getMaximumLength());
        self::assertSame('123-456-7890', $facade->getDefaultValue());
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->check);
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->search);
        self::assertSame('($1) $2-$3', $facade->getPCRE()->replace);
    }

    public function testCopyAsTextDefaultValueLengthError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should not be longer than 10 characters.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 10,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }

    public function testCopyAsTextDefaultValueFormatError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid format of the default value.');

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '1234567890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$command, $field]);
    }
}
