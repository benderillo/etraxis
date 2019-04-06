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

use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\IssuesDomain\Application\CommandHandler\DeleteIssueHandler::handle
 */
class DeleteIssueCommandTest extends TransactionalTestCase
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
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);
        self::assertNotNull($issue);

        $command = new DeleteIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandBus->handle($command);

        /** @var Issue $issue */
        $issue = $this->repository->find($command->issue);
        self::assertNull($issue);
    }

    public function testUnknownIssue()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown issue.');

        $this->loginAs('ldoyle@example.com');

        $command = new DeleteIssueCommand([
            'issue' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandBus->handle($command);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You are not allowed to delete this issue.');

        $this->loginAs('labshire@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new DeleteIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedProject()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new DeleteIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testLockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $command = new DeleteIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandBus->handle($command);
    }

    public function testSuspendedIssue()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        $command = new DeleteIssueCommand([
            'issue' => $issue->id,
        ]);

        $this->commandBus->handle($command);
    }
}
