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

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\IssuesDomain\Model\Entity\Comment
 */
class CommentTest extends TestCase
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

        $event = new Event(EventType::PUBLIC_COMMENT, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $comment = new Comment($event);

        self::assertSame($event, $comment->event);
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'id'        => 4,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'text'      => 'Lorem ipsum',
            'private'   => false,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $event = new Event(EventType::PUBLIC_COMMENT, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $comment = new Comment($event);
        $this->setProperty($comment, 'id', 4);

        $comment->body      = 'Lorem ipsum';
        $comment->isPrivate = false;

        self::assertSame($expected, $comment->jsonSerialize());
    }

    /**
     * @covers ::getters
     */
    public function testIssue()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $event = new Event(EventType::PUBLIC_COMMENT, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $comment = new Comment($event);

        self::assertSame($issue, $comment->issue);
    }
}
