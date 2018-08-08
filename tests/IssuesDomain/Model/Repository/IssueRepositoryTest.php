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

use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\Tests\TransactionalTestCase;

class IssueRepositoryTest extends TransactionalTestCase
{
    /** @var IssueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(IssueRepository::class, $this->repository);
    }

    public function testChangeSubject()
    {
        /** @var Issue $issue */
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $changes = count($this->doctrine->getRepository(Change::class)->findAll());

        $this->repository->changeSubject($issue, $issue->events[0], 'Development task 1');
        $this->doctrine->getManager()->flush();
        self::assertCount($changes, $this->doctrine->getRepository(Change::class)->findAll());

        $this->repository->changeSubject($issue, $issue->events[0], 'Development task X');
        $this->doctrine->getManager()->flush();
        self::assertSame('Development task X', $issue->subject);
        self::assertCount($changes + 1, $this->doctrine->getRepository(Change::class)->findAll());

        /** @var Change $change */
        [$change] = $this->doctrine->getRepository(Change::class)->findBy([], ['id' => 'DESC']);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
        $repository = $this->doctrine->getRepository(StringValue::class);

        self::assertNull($change->field);
        self::assertSame('Development task 1', $repository->find($change->oldValue)->value);
        self::assertSame('Development task X', $repository->find($change->newValue)->value);
    }
}
