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

namespace eTraxis\IssuesDomain\Model\Repository;

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\IssuesDomain\Model\Repository\EventRepository
 */
class EventRepositoryTest extends WebTestCase
{
    /** @var EventRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Event::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(EventRepository::class, $this->repository);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithPrivate()
    {
        $expected = [
            [EventType::ISSUE_CREATED,   'Dorcas Ernser'],
            [EventType::STATE_CHANGED,   'Leland Doyle'],
            [EventType::ISSUE_ASSIGNED,  'Leland Doyle'],
            [EventType::FILE_ATTACHED,   'Leland Doyle'],
            [EventType::FILE_ATTACHED,   'Leland Doyle'],
            [EventType::PUBLIC_COMMENT,  'Leland Doyle'],
            [EventType::ISSUE_CLOSED,    'Dennis Quigley'],
            [EventType::ISSUE_REOPENED,  'Dorcas Ernser'],
            [EventType::STATE_CHANGED,   'Dorcas Ernser'],
            [EventType::ISSUE_ASSIGNED,  'Dorcas Ernser'],
            [EventType::FILE_DELETED,    'Dorcas Ernser'],
            [EventType::PRIVATE_COMMENT, 'Dorcas Ernser'],
            [EventType::FILE_ATTACHED,   'Dennis Quigley'],
            [EventType::PUBLIC_COMMENT,  'Dennis Quigley'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $changes = array_map(function (Event $event) {
            return [$event->type, $event->user->fullname];
        }, $this->repository->findAllByIssue($issue, true));

        self::assertSame($expected, $changes);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueNoPrivate()
    {
        $expected = [
            [EventType::ISSUE_CREATED,  'Dorcas Ernser'],
            [EventType::STATE_CHANGED,  'Leland Doyle'],
            [EventType::ISSUE_ASSIGNED, 'Leland Doyle'],
            [EventType::FILE_ATTACHED,  'Leland Doyle'],
            [EventType::FILE_ATTACHED,  'Leland Doyle'],
            [EventType::PUBLIC_COMMENT, 'Leland Doyle'],
            [EventType::ISSUE_CLOSED,   'Dennis Quigley'],
            [EventType::ISSUE_REOPENED, 'Dorcas Ernser'],
            [EventType::STATE_CHANGED,  'Dorcas Ernser'],
            [EventType::ISSUE_ASSIGNED, 'Dorcas Ernser'],
            [EventType::FILE_DELETED,   'Dorcas Ernser'],
            [EventType::FILE_ATTACHED,  'Dennis Quigley'],
            [EventType::PUBLIC_COMMENT, 'Dennis Quigley'],
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $changes = array_map(function (Event $event) {
            return [$event->type, $event->user->fullname];
        }, $this->repository->findAllByIssue($issue, false));

        self::assertSame($expected, $changes);
    }
}
