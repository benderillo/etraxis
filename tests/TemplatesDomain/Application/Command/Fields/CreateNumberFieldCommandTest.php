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

class CreateNumberFieldCommandTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Week number']);
        self::assertNull($field);

        $command = new CreateNumberFieldCommand([
            'state'        => $state->id,
            'name'         => 'Week number',
            'required'     => true,
            'minimumValue' => 1,
            'maximumValue' => 53,
            'defaultValue' => 7,
        ]);

        $result = $this->commandbus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Week number']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::NUMBER, $field->type);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\NumberInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame(1, $facade->getMinimumValue());
        self::assertSame(53, $facade->getMaximumValue());
        self::assertSame(7, $facade->getDefaultValue());
    }
}
