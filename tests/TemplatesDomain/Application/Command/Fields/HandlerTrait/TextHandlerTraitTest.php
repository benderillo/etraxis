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

namespace eTraxis\TemplatesDomain\Application\Command\Fields\HandlerTrait;

use eTraxis\TemplatesDomain\Application\Command\Fields as Command;
use eTraxis\TemplatesDomain\Application\CommandHandler\Fields\HandlerTrait\TextHandlerTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TextHandlerTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    /** @var TextHandlerTrait $handler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);

        $this->handler = new class() {
            use TextHandlerTrait;
        };
    }

    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::TEXT, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    public function testCopyCommandToFieldSuccess()
    {
        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

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

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame(20, $facade->getMaximumLength());
        self::assertSame('123-456-7890', $facade->getDefaultValue());
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->check);
        self::assertSame('(\d{3})-(\d{3})-(\d{4})', $facade->getPCRE()->search);
        self::assertSame('($1) $2-$3', $facade->getPCRE()->replace);
    }

    public function testCopyCommandToFieldDefaultValueLengthError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Default value should not be longer than 10 characters.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 10,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    public function testCopyCommandToFieldDefaultValueFormatError()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid format of the default value.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '1234567890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    public function testCopyCommandToFieldUnsupportedCommand()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported command.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    public function testCopyCommandToFieldUnsupportedFieldType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported field type.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        $command = new Command\UpdateTextFieldCommand([
            'maximumLength' => 20,
            'defaultValue'  => '123-456-7890',
            'pcreCheck'     => '(\d{3})-(\d{3})-(\d{4})',
            'pcreSearch'    => '(\d{3})-(\d{3})-(\d{4})',
            'pcreReplace'   => '($1) $2-$3',
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
