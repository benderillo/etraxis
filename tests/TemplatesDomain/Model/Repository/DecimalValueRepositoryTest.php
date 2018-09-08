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
use eTraxis\Tests\TransactionalTestCase;

class DecimalValueRepositoryTest extends TransactionalTestCase
{
    /** @var DecimalValueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(DecimalValue::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(DecimalValueRepository::class, $this->repository);
    }

    public function testFind()
    {
        $expected = $this->repository->findOneBy(['value' => '98.49']);
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }

    public function testFindOne()
    {
        $expected = '3.14159292';

        $count = count($this->repository->findAll());

        /** @var DecimalValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var DecimalValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertSame($value1, $value);
        self::assertSame($expected, $value->value);
        self::assertCount($count + 1, $this->repository->findAll());

        // Second attempt.
        $value2 = $this->repository->get($expected);

        self::assertSame($value1, $value2);
        self::assertCount($count + 1, $this->repository->findAll());
    }

    public function testWarmup()
    {
        /** @var DecimalValue $value1 */
        $value1 = $this->repository->findOneBy(['value' => '98.49']);

        /** @var DecimalValue $value2 */
        $value2 = $this->repository->findOneBy(['value' => '99.05']);

        self::assertSame(2, $this->repository->warmup([
            self::UNKNOWN_ENTITY_ID,
            $value1->id,
            $value2->id,
        ]));
    }
}
