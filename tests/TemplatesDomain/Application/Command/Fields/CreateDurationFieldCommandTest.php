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

class CreateDurationFieldCommandTest extends TransactionalTestCase
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
        $field = $this->repository->findOneBy(['name' => 'Time']);
        self::assertNull($field);

        $command = new CreateDurationFieldCommand([
            'state'        => $state->id,
            'name'         => 'Time',
            'required'     => true,
            'minimumValue' => '0:00',
            'maximumValue' => '23:59',
            'defaultValue' => '8:00',
        ]);

        $result = $this->commandbus->handle($command);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['name' => 'Time']);
        self::assertNotNull($field);
        self::assertSame($result, $field);
        self::assertSame(FieldType::DURATION, $field->type);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DurationInterface $facade */
        $facade = $field->getFacade($this->manager);
        self::assertSame('0:00', $facade->getMinimumValue());
        self::assertSame('23:59', $facade->getMaximumValue());
        self::assertSame('8:00', $facade->getDefaultValue());
    }
}
