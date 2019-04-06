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

use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\Tests\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository
 */
class TextValueRepositoryTest extends TransactionalTestCase
{
    /** @var TextValueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(TextValue::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(TextValueRepository::class, $this->repository);
    }

    /**
     * @covers ::find
     */
    public function testFind()
    {
        $expected = $this->repository->findOneBy(['value' => 'Quas sunt reprehenderit vero accusantium.']);
        self::assertNotNull($expected);

        $value = $this->repository->find($expected->id);
        self::assertSame($expected, $value);
    }

    /**
     * @covers ::get
     */
    public function testFindOne()
    {
        $expected = 'Issue tracking system with customizable workflows.';

        $count = count($this->repository->findAll());

        /** @var TextValue $value */
        $value = $this->repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $this->repository->get($expected);

        /** @var TextValue $value */
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
        /** @var TextValue $value1 */
        $value1 = $this->repository->findOneBy(['value' => 'Quas sunt reprehenderit vero accusantium.']);

        /** @var TextValue $value2 */
        $value2 = $this->repository->findOneBy(['value' => 'Velit voluptatem rerum nulla quos soluta excepturi omnis.']);

        self::assertSame(2, $this->repository->warmup([
            self::UNKNOWN_ENTITY_ID,
            $value1->id,
            $value2->id,
        ]));
    }
}
