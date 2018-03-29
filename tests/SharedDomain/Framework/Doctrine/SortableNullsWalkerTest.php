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

namespace eTraxis\SharedDomain\Framework\Doctrine;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\WebTestCase;

class SortableNullsWalkerTest extends WebTestCase
{
    public function testAsc()
    {
        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        $users = $repository
            ->createQueryBuilder('user')
            ->orderBy('user.description', 'ASC')
            ->addOrderBy('user.email', 'ASC')
            ->getQuery()
            ->execute();

        $expected = [
            'artem@example.com',    // the description is NULL here
            'admin@example.com',
        ];

        $actual = array_map(function (User $user) {
            return $user->getUsername();
        }, $users);

        self::assertSame($expected, array_slice($actual, 0, 2));
    }

    public function testDesc()
    {
        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        $users = $repository
            ->createQueryBuilder('user')
            ->orderBy('user.description', 'DESC')
            ->addOrderBy('user.email', 'ASC')
            ->getQuery()
            ->execute();

        $expected = [
            'admin@example.com',
            'artem@example.com',    // the description is NULL here
        ];

        $actual = array_map(function (User $user) {
            return $user->getUsername();
        }, $users);

        self::assertSame($expected, array_slice($actual, -2));
    }
}
