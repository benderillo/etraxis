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

namespace eTraxis\TemplatesDomain\Application\Service;

use eTraxis\TemplatesDomain\Application\Command\Fields as Command;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FieldServiceTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $fieldRepository;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository */
    protected $decimalRepository;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository */
    protected $stringRepository;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository */
    protected $textRepository;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository */
    protected $listRepository;

    /** @var FieldService $service */
    protected $service;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldRepository   = $this->doctrine->getRepository(Field::class);
        $this->decimalRepository = $this->doctrine->getRepository(DecimalValue::class);
        $this->stringRepository  = $this->doctrine->getRepository(StringValue::class);
        $this->textRepository    = $this->doctrine->getRepository(TextValue::class);
        $this->listRepository    = $this->doctrine->getRepository(ListItem::class);

        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->client->getContainer()->get('translator');

        $this->service = new FieldService($translator, $this->decimalRepository, $this->stringRepository, $this->textRepository, $this->listRepository);
    }

    public function testCopyAsCheckboxSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        self::assertFalse($field->asCheckbox()->getDefaultValue());

        $command = new Command\UpdateCheckboxFieldCommand([
            'defaultValue' => true,
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertTrue($field->asCheckbox()->getDefaultValue());
    }

    public function testCopyAsDateSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Due date'], ['id' => 'ASC']);

        self::assertSame(0, $field->asDate()->getMinimumValue());
        self::assertSame(14, $field->asDate()->getMaximumValue());
        self::assertSame(14, $field->asDate()->getDefaultValue());

        $command = new Command\UpdateDateFieldCommand([
            'minimumValue' => '1',
            'maximumValue' => '7',
            'defaultValue' => '3',
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame(1, $field->asDate()->getMinimumValue());
        self::assertSame(7, $field->asDate()->getMaximumValue());
        self::assertSame(3, $field->asDate()->getDefaultValue());
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

        $this->service->copyCommandToField($command, $field);
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

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsDecimalSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Test coverage'], ['id' => 'ASC']);

        self::assertSame('0.0000000000', $field->asDecimal($this->decimalRepository)->getMinimumValue());
        self::assertSame('100.0000000000', $field->asDecimal($this->decimalRepository)->getMaximumValue());
        self::assertNull($field->asDecimal($this->decimalRepository)->getDefaultValue());

        $command = new Command\UpdateDecimalFieldCommand([
            'minimumValue' => '1',
            'maximumValue' => '10',
            'defaultValue' => '5',
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame('1', $field->asDecimal($this->decimalRepository)->getMinimumValue());
        self::assertSame('10', $field->asDecimal($this->decimalRepository)->getMaximumValue());
        self::assertSame('5', $field->asDecimal($this->decimalRepository)->getDefaultValue());
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

        $this->service->copyCommandToField($command, $field);
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

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsDurationSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        self::assertSame('0:00', $field->asDuration()->getMinimumValue());
        self::assertSame('999999:59', $field->asDuration()->getMaximumValue());
        self::assertNull($field->asDuration()->getDefaultValue());

        $command = new Command\UpdateDurationFieldCommand([
            'minimumValue' => '0:01',
            'maximumValue' => '0:59',
            'defaultValue' => '0:30',
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame('0:01', $field->asDuration()->getMinimumValue());
        self::assertSame('0:59', $field->asDuration()->getMaximumValue());
        self::assertSame('0:30', $field->asDuration()->getDefaultValue());
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

        $this->service->copyCommandToField($command, $field);
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

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsIssueSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->service->copyCommandToField($command, $field);

        self::assertTrue(true);
    }

    public function testCopyAsListSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $this->listRepository->findBy(['value' => 2], ['id' => 'ASC']);

        self::assertNull($field->asList($this->listRepository)->getDefaultValue());

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame($item, $field->asList($this->listRepository)->getDefaultValue());

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => null,
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertNull($field->asList($this->listRepository)->getDefaultValue());
    }

    public function testCopyAsListUnknownItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsListWrongItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $this->listRepository->findBy(['value' => 2], ['id' => 'DESC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsNumberSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        self::assertSame(0, $field->asNumber()->getMinimumValue());
        self::assertSame(1000000000, $field->asNumber()->getMaximumValue());
        self::assertNull($field->asNumber()->getDefaultValue());

        $command = new Command\UpdateNumberFieldCommand([
            'minimumValue' => -100000,
            'maximumValue' => +100000,
            'defaultValue' => 100,
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame(-100000, $field->asNumber()->getMinimumValue());
        self::assertSame(100000, $field->asNumber()->getMaximumValue());
        self::assertSame(100, $field->asNumber()->getDefaultValue());
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

        $this->service->copyCommandToField($command, $field);
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

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsStringSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Commit ID'], ['id' => 'ASC']);

        self::assertSame(40, $field->asString($this->stringRepository)->getMaximumLength());
        self::assertNull($field->asString($this->stringRepository)->getDefaultValue());
        self::assertNull($field->asString($this->stringRepository)->getPCRE()->check);
        self::assertNull($field->asString($this->stringRepository)->getPCRE()->search);
        self::assertNull($field->asString($this->stringRepository)->getPCRE()->replace);

        $command = new Command\UpdateStringFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame(20, $field->asString($this->stringRepository)->getMaximumLength());
        self::assertSame('123-456-7890', $field->asString($this->stringRepository)->getDefaultValue());
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $field->asString($this->stringRepository)->getPCRE()->check);
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $field->asString($this->stringRepository)->getPCRE()->search);
        self::assertSame('($1) $2-$3', $field->asString($this->stringRepository)->getPCRE()->replace);
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

        $this->service->copyCommandToField($command, $field);
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

        $this->service->copyCommandToField($command, $field);
    }

    public function testCopyAsTextSuccess()
    {
        /** @var Field $field */
        [$field] = $this->fieldRepository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        self::assertSame(4000, $field->asText($this->textRepository)->getMaximumLength());
        self::assertNull($field->asText($this->textRepository)->getDefaultValue());
        self::assertNull($field->asText($this->textRepository)->getPCRE()->check);
        self::assertNull($field->asText($this->textRepository)->getPCRE()->search);
        self::assertNull($field->asText($this->textRepository)->getPCRE()->replace);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $field = $this->service->copyCommandToField($command, $field);

        self::assertSame(20, $field->asText($this->textRepository)->getMaximumLength());
        self::assertSame('123-456-7890', $field->asText($this->textRepository)->getDefaultValue());
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $field->asText($this->textRepository)->getPCRE()->check);
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $field->asText($this->textRepository)->getPCRE()->search);
        self::assertSame('($1) $2-$3', $field->asText($this->textRepository)->getPCRE()->replace);
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

        $this->service->copyCommandToField($command, $field);
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

        $this->service->copyCommandToField($command, $field);
    }
}
