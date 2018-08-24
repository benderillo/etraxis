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
use eTraxis\IssuesDomain\Model\Entity\Comment;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddCommentCommandTest extends TransactionalTestCase
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
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'jmueller@example.com']);

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $events   = count($issue->events);
        $comments = count($this->doctrine->getRepository(Comment::class)->findAll());

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($issue);

        self::assertCount($events + 1, $issue->events);
        self::assertCount($comments + 1, $this->doctrine->getRepository(Comment::class)->findAll());

        $events = $issue->events;
        $event  = end($events);

        self::assertSame(EventType::PUBLIC_COMMENT, $event->type);
        self::assertSame($issue, $event->issue);
        self::assertSame($user, $event->user);
        self::assertLessThanOrEqual(1, time() - $event->createdAt);
        self::assertNull($event->parameter);

        /** @var Comment $comment */
        $comment = $this->doctrine->getRepository(Comment::class)->findOneBy(['event' => $event]);

        self::assertSame('Test comment.', $comment->body);
        self::assertFalse($comment->isPrivate);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('jmueller@example.com');

        $command = new AddCommentCommand([
            'issue'   => self::UNKNOWN_ENTITY_ID,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);
    }

    public function testPublicCommentAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue.');

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);
    }

    public function testPrivateCommentAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to comment this issue privately.');

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => true,
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);
    }

    public function testFrozenIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $command = new AddCommentCommand([
            'issue'   => $issue->id,
            'body'    => 'Test comment.',
            'private' => false,
        ]);

        $this->commandbus->handle($command);
    }
}
