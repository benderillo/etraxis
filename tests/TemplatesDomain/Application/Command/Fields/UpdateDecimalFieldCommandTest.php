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
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Fields\UpdateDecimalFieldHandler::handle
 */
class UpdateDecimalFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Test coverage']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertSame('0', $facade->getMinimumValue());
        self::assertSame('100', $facade->getMaximumValue());
        self::assertNull($facade->getDefaultValue());

        $command = new UpdateDecimalFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'minimum'  => '0.01',
            'maximum'  => '99.99',
            'default'  => '50.00',
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame('0.01', $facade->getMinimumValue());
        self::assertSame('99.99', $facade->getMaximumValue());
        self::assertSame('50', $facade->getDefaultValue());
    }
}
