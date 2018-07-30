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

namespace eTraxis\TemplatesDomain\Model\Repository;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\WebTestCase;

class ListItemRepositoryTest extends WebTestCase
{
    /** @var ListItemRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(ListItemRepository::class, $this->repository);
    }

    public function testFindAllByField()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $items = $this->repository->findAllByField($field);

        $expected = [
            'high',
            'normal',
            'low',
        ];

        $actual = array_map(function (ListItem $item) {
            return $item->text;
        }, $items);

        self::assertCount(3, $items);
        self::assertSame($expected, $actual);
    }

    public function testFindOneByValueSuccess()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 2);

        self::assertInstanceOf(ListItem::class, $item);
        self::assertSame('normal', $item->text);
    }

    public function testFindOneByValueUnknown()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 4);

        self::assertNull($item);
    }

    public function testFindOneByValueWrongField()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByValue($field, 2);

        self::assertNull($item);
    }

    public function testFindOneByTextSuccess()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByText($field, 'normal');

        self::assertInstanceOf(ListItem::class, $item);
        self::assertSame(2, $item->value);
    }

    public function testFindOneByTextUnknown()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByText($field, 'unknown');

        self::assertNull($item);
    }

    public function testFindOneByTextWrongField()
    {
        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Description', 'removedAt' => null], ['id' => 'ASC']);

        $item = $this->repository->findOneByText($field, 'normal');

        self::assertNull($item);
    }
}
