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

use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Entity\LastRead;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;

class LastReadRepositoryTest extends TransactionalTestCase
{
    /** @var LastReadRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(LastRead::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(LastReadRepository::class, $this->repository);
    }

    public function testMarkAsReadExisting()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNotNull($read);
        self::assertGreaterThan(2, time() - $read->readAt);

        $this->repository->markAsRead($issue, $user);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNotNull($read);
        self::assertLessThanOrEqual(2, time() - $read->readAt);
    }

    public function testMarkAsReadNew()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNull($read);

        $this->repository->markAsRead($issue, $user);

        /** @var LastRead $read */
        $read = $this->repository->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        self::assertNotNull($read);
        self::assertLessThanOrEqual(2, time() - $read->readAt);
    }
}
