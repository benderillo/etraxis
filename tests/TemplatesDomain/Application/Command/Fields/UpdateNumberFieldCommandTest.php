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

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\TransactionalTestCase;

/**
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Fields\UpdateNumberFieldHandler::handle
 */
class UpdateNumberFieldCommandTest extends TransactionalTestCase
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->manager    = $this->doctrine->getManager();
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame(0, $facade->getMinimumValue());
        self::assertSame(1000000000, $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new UpdateNumberFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'minimum'  => 1,
            'maximum'  => 999999,
            'default'  => 10,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame(1, $facade->getMinimumValue());
        self::assertSame(999999, $facade->getMaximumValue());
        self::assertSame(10, $facade->getDefaultValue());
    }
}
