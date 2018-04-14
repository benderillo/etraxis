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

namespace eTraxis\TemplatesDomain\Model\FieldTypes;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;

class ListTraitTest extends TransactionalTestCase
{
    use ReflectionTrait;

    public function testDefaultValue()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository $fieldRepository */
        $fieldRepository = $this->doctrine->getRepository(Field::class);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $itemRepository */
        $itemRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var Field[] $fields */
        $fields = $fieldRepository->findBy([
            'name' => 'Priority',
        ]);

        /** @var ListItem $item1 */
        $item1 = $itemRepository->findOneBy([
            'field' => $fields[0],
            'value' => 1,
        ]);

        /** @var ListItem $item2 */
        $item2 = $itemRepository->findOneBy([
            'field' => $fields[1],
            'value' => 2,
        ]);

        $field      = $fields[0]->asList($itemRepository);
        $parameters = $this->getProperty($fields[0], 'parameters');

        $field->setDefaultValue($item1);
        self::assertSame($item1, $field->getDefaultValue());
        self::assertSame($item1->id, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue($item2);
        self::assertSame($item1, $field->getDefaultValue());
        self::assertSame($item1->id, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue(null);
        self::assertNull($field->getDefaultValue());
        self::assertNull($this->getProperty($parameters, 'defaultValue'));
    }
}
