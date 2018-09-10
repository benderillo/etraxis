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

use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\WebTestCase;

class FieldRepositoryTest extends WebTestCase
{
    use ReflectionTrait;

    /** @var FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(FieldRepository::class, $this->repository);
    }

    public function testFind()
    {
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertSame($field, $this->repository->find($field->id));
    }

    public function testFindAll()
    {
        $fields = $this->repository->findAll();

        self::assertCount(48, $fields);
    }

    public function testFindBy()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field[] $fields */
        $fields = $this->repository->findBy(['state' => $state], ['name' => 'ASC']);

        self::assertCount(2, $fields);
        self::assertSame('Issue ID', $fields[0]->name);
        self::assertSame('Task ID', $fields[1]->name);
    }

    public function testFindOneBy()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['state' => $state], ['name' => 'ASC']);

        self::assertSame('Issue ID', $field->name);
    }

    public function testWarmupCache1()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $textRepository = $this->doctrine->getRepository(TextValue::class);
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var \Psr\SimpleCache\CacheInterface $textCache */
        $textCache = $this->getProperty($textRepository, 'cache');

        /** @var \Psr\SimpleCache\CacheInterface $listCache */
        $listCache = $this->getProperty($listRepository, 'cache');

        $defaultText   = $textRepository->findOneBy(['value' => 'How to reproduce:']);
        [$defaultItem] = $listRepository->findBy(['value' => 2], ['id' => 'ASC']);

        self::assertFalse($textCache->has("{$defaultText->id}"));
        self::assertFalse($listCache->has("{$defaultItem->id}"));

        $this->repository->findBy(['state' => $state]);

        self::assertTrue($textCache->has("{$defaultText->id}"));
        self::assertTrue($listCache->has("{$defaultItem->id}"));
    }

    public function testWarmupCache2()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);
        $stringRepository  = $this->doctrine->getRepository(StringValue::class);

        /** @var \Psr\SimpleCache\CacheInterface $decimalCache */
        $decimalCache = $this->getProperty($decimalRepository, 'cache');

        /** @var \Psr\SimpleCache\CacheInterface $stringCache */
        $stringCache = $this->getProperty($stringRepository, 'cache');

        $minimum = $decimalRepository->findOneBy(['value' => '0']);
        $maximum = $decimalRepository->findOneBy(['value' => '100']);
        $default = $stringRepository->findOneBy(['value' => 'Git commit ID']);

        self::assertFalse($decimalCache->has("{$minimum->id}"));
        self::assertFalse($decimalCache->has("{$maximum->id}"));
        self::assertFalse($stringCache->has("{$default->id}"));

        $this->repository->findBy(['state' => $state]);

        self::assertTrue($decimalCache->has("{$minimum->id}"));
        self::assertTrue($decimalCache->has("{$maximum->id}"));
        self::assertTrue($stringCache->has("{$default->id}"));
    }
}
