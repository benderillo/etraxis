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
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReassignIssueCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\IssuesDomain\Model\Repository\IssueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $events = count($issue->events);

        self::assertSame('akoepp@example.com', $issue->responsible->email);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('nhills@example.com', $issue->responsible->email);

        self::assertCount($events + 1, $issue->events);

        $event = $issue->events[$events];

        self::assertSame(EventType::ISSUE_ASSIGNED, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertLessThanOrEqual(2, time() - $event->createdAt);
        self::assertSame($user->id, $event->parameter);
    }

    public function testSuccessSameResponsible()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'akoepp@example.com']);

        $events = count($issue->events);

        self::assertSame('akoepp@example.com', $issue->responsible->email);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertSame('akoepp@example.com', $issue->responsible->email);

        self::assertCount($events, $issue->events);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand([
            'issue'       => self::UNKNOWN_ENTITY_ID,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown user.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to reassign this issue.');

        $this->loginAs('nhills@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testResponsibleDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to reassign this issue.');

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $command = new ReassignIssueCommand([
            'issue'       => $issue->id,
            'responsible' => $user->id,
        ]);

        $this->commandbus->handle($command);
    }
}
