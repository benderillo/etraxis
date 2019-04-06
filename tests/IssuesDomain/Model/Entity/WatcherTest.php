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

namespace eTraxis\IssuesDomain\Model\Entity;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\IssuesDomain\Model\Entity\Watcher
 */
class WatcherTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $watcher = new Watcher($issue, $user);

        self::assertSame($issue, $watcher->issue);
        self::assertSame($user, $watcher->user);
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'id'       => 1,
            'email'    => 'anna@example.com',
            'fullname' => 'Anna Rodygina',
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $watcher = new Watcher($issue, $user);

        self::assertSame($expected, $watcher->jsonSerialize());
    }
}
