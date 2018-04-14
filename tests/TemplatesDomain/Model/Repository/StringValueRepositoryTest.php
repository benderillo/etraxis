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

class StringValueRepositoryTest extends TransactionalTestCase
{
    public function testRepository()
    {
        $repository = $this->doctrine->getRepository(StringValue::class);

        self::assertInstanceOf(StringValueRepository::class, $repository);
    }

    public function testSave()
    {
        $expected = 'eTraxis';

        /** @var StringValueRepository $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        $count = count($repository->findAll());

        /** @var StringValue $value */
        $value = $repository->findOneBy(['value' => $expected]);

        self::assertNull($value);

        // First attempt.
        $value1 = $repository->get($expected);

        /** @var StringValue $value */
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
