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
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Fields\UpdateCheckboxFieldHandler::handle
 */
class UpdateCheckboxFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'New feature']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\CheckboxInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertFalse($facade->getDefaultValue());

        $command = new UpdateCheckboxFieldCommand([
            'field'    => $field->id,
            'name'     => $field->name,
            'required' => $field->isRequired,
            'default'  => true,
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertTrue($facade->getDefaultValue());
    }
}
