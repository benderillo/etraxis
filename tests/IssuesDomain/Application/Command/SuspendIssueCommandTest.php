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

namespace eTraxis\IssuesDomain\Application\Command;

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\TransactionalTestCase;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SuspendIssueCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\IssuesDomain\Model\Repository\IssueRepository */
    protected $repository;

    /** @var \DateTime */
    protected $date;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);

        $this->date = date_create();
        $this->date->setTimezone(timezone_open('UTC'));
        $this->date->setTimestamp(time() + 86400);
        $this->date->setTime(0, 0);
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        self::assertFalse($issue->isSuspended);

        $events = count($issue->events);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertTrue($issue->isSuspended);
        self::assertCount($events + 1, $issue->events);

        $event = $issue->events[$events];

        self::assertSame(EventType::ISSUE_SUSPENDED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
    }

    public function testValidationRequiredFields()
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage('Validation failed for eTraxis\IssuesDomain\Application\Command\SuspendIssueCommand with 1 violation(s).');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testValidationInvalidDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Date must be in future.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => gmdate('Y-m-d'),
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        $command = new SuspendIssueCommand([
            'issue' => self::UNKNOWN_ENTITY_ID,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to suspend this issue.');

        $this->loginAs('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandbus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $command = new SuspendIssueCommand([
            'issue' => $issue->id,
            'date'  => $this->date->format('Y-m-d'),
        ]);

        $this->commandbus->handle($command);
    }
}
