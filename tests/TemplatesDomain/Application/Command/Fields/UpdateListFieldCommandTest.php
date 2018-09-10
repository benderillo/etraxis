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
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\TransactionalTestCase;

class UpdateListFieldCommandTest extends TransactionalTestCase
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
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority']);

        /** @var ListItem $item */
        [$item] = $this->doctrine->getRepository(ListItem::class)->findBy(['field' => $field]);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($this->manager);

        self::assertNotSame($item, $facade->getDefaultValue());

        $command = new UpdateListFieldCommand([
            'field'        => $field->id,
            'name'         => $field->name,
            'required'     => $field->isRequired,
            'defaultValue' => $item->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame($item, $facade->getDefaultValue());
    }
}
