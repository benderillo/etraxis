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
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $event = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $user->id);

        self::assertSame(EventType::ISSUE_ASSIGNED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertSame(1, $event->parameter);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
    }

    public function testJsonSerializeIssueCreated()
    {
        $expected = [
            'type'      => EventType::ISSUE_CREATED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'state'     => 2,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $state = new State(new Template(new Project()), StateType::INITIAL);
        $this->setProperty($state, 'id', 2);

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_CREATED, $issue, $user, $state->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeIssueEdited()
    {
        $expected = [
            'type'      => EventType::ISSUE_EDITED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeStateChanged()
    {
        $expected = [
            'type'      => EventType::STATE_CHANGED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'state'     => 3,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue($user);
        $event = new Event(EventType::STATE_CHANGED, $issue, $user, $state->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeIssueReopened()
    {
        $expected = [
            'type'      => EventType::ISSUE_REOPENED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'state'     => 2,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $state = new State(new Template(new Project()), StateType::INITIAL);
        $this->setProperty($state, 'id', 2);

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_REOPENED, $issue, $user, $state->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeIssueClosed()
    {
        $expected = [
            'type'      => EventType::ISSUE_CLOSED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'state'     => 4,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $state = new State(new Template(new Project()), StateType::FINAL);
        $this->setProperty($state, 'id', 4);

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_CLOSED, $issue, $user, $state->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeIssueAssigned()
    {
        $expected = [
            'type'      => EventType::ISSUE_ASSIGNED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'assignee'  => 5,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $responsible = new User();
        $this->setProperty($responsible, 'id', 5);

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $responsible->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeIssueSuspended()
    {
        $expected = [
            'type'      => EventType::ISSUE_SUSPENDED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_SUSPENDED, $issue, $user);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeIssueResumed()
    {
        $expected = [
            'type'      => EventType::ISSUE_RESUMED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $event = new Event(EventType::ISSUE_RESUMED, $issue, $user);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializePublicComment()
    {
        $expected = [
            'type'      => EventType::PUBLIC_COMMENT,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $event = new Event(EventType::PUBLIC_COMMENT, $issue, $user);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializePrivateComment()
    {
        $expected = [
            'type'      => EventType::PRIVATE_COMMENT,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $event = new Event(EventType::PRIVATE_COMMENT, $issue, $user);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeFileAttached()
    {
        $expected = [
            'type'      => EventType::FILE_ATTACHED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'file'      => 7,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);
        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);
        $this->setProperty($event, 'id', 6);

        $file = new File($event, 'example.csv', 2309, 'text/csv');
        $this->setProperty($file, 'id', 7);
        $this->setProperty($event, 'parameter', $file->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeFileDeleted()
    {
        $expected = [
            'type'      => EventType::FILE_DELETED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'file'      => 7,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $issue = new Issue($user);

        $file = new File(new Event(EventType::FILE_ATTACHED, $issue, $user), 'example.csv', 2309, 'text/csv');
        $this->setProperty($file, 'id', 7);

        $event = new Event(EventType::FILE_DELETED, $issue, $user, $file->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeDependencyAdded()
    {
        $expected = [
            'type'      => EventType::DEPENDENCY_ADDED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'issue'     => 8,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $dependency = new Issue($user);
        $this->setProperty($dependency, 'id', 8);

        $issue = new Issue($user);
        $event = new Event(EventType::DEPENDENCY_ADDED, $issue, $user, $dependency->id);

        self::assertSame($expected, $event->jsonSerialize());
    }

    public function testJsonSerializeDependencyRemoved()
    {
        $expected = [
            'type'      => EventType::DEPENDENCY_REMOVED,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'issue'     => 8,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $dependency = new Issue($user);
        $this->setProperty($dependency, 'id', 8);

        $issue = new Issue($user);
        $event = new Event(EventType::DEPENDENCY_REMOVED, $issue, $user, $dependency->id);

        self::assertSame($expected, $event->jsonSerialize());
    }
}
