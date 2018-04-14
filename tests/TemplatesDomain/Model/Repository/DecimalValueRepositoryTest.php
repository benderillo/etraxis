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
    public function testRepository()
    {
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        self::assertInstanceOf(DecimalValueRepository::class, $repository);
    }

    public function testFindOne()
    {
        $expected = '3.14159292';

        /** @var DecimalValueRepository $repository */
        $repository = $this->doctrine->getRepository(DecimalValue::class);

        $count = count($repository->findAll());

        /** @var DecimalValue $value */
        $value = $repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $repository->get($expected);

        /** @var DecimalValue $value */
        $value = $repository->findOneBy(['value' => $expected]);

        self::assertSame($value1, $value);
        self::assertSame($expected, $value->value);
        self::assertCount($count + 1, $repository->findAll());

        // Second attempt.
        $value2 = $repository->get($expected);

        self::assertSame($value1, $value2);
        self::assertCount($count + 1, $repository->findAll());
    }
}
