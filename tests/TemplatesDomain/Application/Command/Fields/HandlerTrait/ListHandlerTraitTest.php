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
use eTraxis\TemplatesDomain\Application\CommandHandler\Fields\HandlerTrait\ListHandlerTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListHandlerTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    /** @var ListHandlerTrait $handler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->client->getContainer()->get('translator');
        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);

        $this->handler = new class() {
            use ListHandlerTrait;
        };
    }

    public function testGetSupportedFieldType()
    {
        self::assertSame(FieldType::LIST, $this->callMethod($this->handler, 'getSupportedFieldType'));
    }

    public function testCopyCommandToFieldSuccess()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($this->manager);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(2, $facade->getDefaultValue()->value);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertSame(1, $facade->getDefaultValue()->value);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => null,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);

        self::assertNull($facade->getDefaultValue());
    }

    public function testCopyCommandToFieldUnknownItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    public function testCopyCommandToFieldWrongItem()
    {
        $this->expectException(NotFoundHttpException::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 2], ['id' => 'DESC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    public function testCopyCommandToFieldUnsupportedCommand()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported command.');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new Command\UpdateIssueFieldCommand();

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }

    public function testCopyCommandToFieldUnsupportedFieldType()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported field type.');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
        $repository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Issue ID'], ['id' => 'ASC']);

        /** @var ListItem $item */
        [$item] = $repository->findBy(['value' => 2], ['id' => 'ASC']);

        $command = new Command\UpdateListFieldCommand([
            'defaultValue' => $item->id,
        ]);

        $this->callMethod($this->handler, 'copyCommandToField', [$this->translator, $this->manager, $command, $field]);
    }
}
