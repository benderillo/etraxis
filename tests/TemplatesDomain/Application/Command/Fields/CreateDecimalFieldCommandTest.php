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

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;

/**
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Fields\CreateDecimalFieldHandler::handle
 */
class CreateDecimalFieldCommandTest extends TransactionalTestCase
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

        /** @var State $state */
        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        self::assertNull($field);

        $command = new CreateDecimalFieldCommand([
            'state'    => $state->id,
            'name'     => 'Coverage',
            'required' => true,
            'minimum'  => '0.00',
            'maximum'  => '100.00',
            'default'  => '3.1415',
        ]);

        $result = $this->commandBus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Coverage']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::DECIMAL, $field->type);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame('0', $facade->getMinimumValue());
        self::assertSame('100', $facade->getMaximumValue());
        self::assertSame('3.1415', $facade->getDefaultValue());
    }
}
