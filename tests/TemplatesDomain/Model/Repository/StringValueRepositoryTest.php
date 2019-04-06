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

use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\Tests\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository
 */
class StringValueRepositoryTest extends TransactionalTestCase
{
    /** @var StringValueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(StringValue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(StringValueRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        $expected = $this->repository->findOneBy(['value' => 'Development task 1']);
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }

    /**
     * @covers ::get
     */
    public function testFindOne()
    {
        $expected = 'eTraxis';

        $count = count($this->repository->findAll());

        /** @var StringValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var StringValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertSame($value1, $value);
        self::assertSame($expected, $value->value);
        self::assertCount($count + 1, $this->repository->findAll());

        // Second attempt.
        $value2 = $this->repository->get($expected);

        self::assertSame($value1, $value2);
        self::assertCount($count + 1, $this->repository->findAll());
    }

    /**
     * @covers ::warmup
     */
    public function testWarmup()
    {
        /** @var StringValue $value1 */
        $value1 = $this->repository->findOneBy(['value' => 'Task 1']);

        /** @var StringValue $value2 */
        $value2 = $this->repository->findOneBy(['value' => 'Development task 1']);

        self::assertSame(2, $this->repository->warmup([
            self::UNKNOWN_ENTITY_ID,
            $value1->id,
            $value2->id,
        ]));
    }
}
