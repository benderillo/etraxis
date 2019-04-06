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
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\IssuesDomain\Model\Repository\ChangeRepository
 */
class ChangeRepositoryTest extends WebTestCase
{
    /** @var ChangeRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Change::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(ChangeRepository::class, $this->repository);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssue()
    {
        $expected = [
            'Priority',
            'Description',
            'Due date',
        ];

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $changes = array_map(function (Change $change) {
            return $change->field === null ? 'Subject' : $change->field->name;
        }, $this->repository->findAllByIssue($issue, $user));

        self::assertSame($expected, $changes);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueByRole()
    {
        $expected1 = [
            'Priority',
            'Description',
            'Due date',
        ];

        $expected2 = [
            'Priority',
            'Description',
        ];

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var User $user1 */
        $user1 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'akoepp@example.com']);

        /** @var User $user2 */
        $user2 = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        $changes1 = array_map(function (Change $change) {
            return $change->field === null ? 'Subject' : $change->field->name;
        }, $this->repository->findAllByIssue($issue, $user1));

        $changes2 = array_map(function (Change $change) {
            return $change->field === null ? 'Subject' : $change->field->name;
        }, $this->repository->findAllByIssue($issue, $user2));

        self::assertSame($expected1, $changes1);
        self::assertSame($expected2, $changes2);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithSubject()
    {
        $expected = [
            'Subject',
            'Priority',
        ];

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        $changes = array_map(function (Change $change) {
            return $change->field === null ? 'Subject' : $change->field->name;
        }, $this->repository->findAllByIssue($issue, $user));

        self::assertSame($expected, $changes);
    }
}
