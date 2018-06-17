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

namespace eTraxis\IssuesDomain\Application\Voter;

use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class IssueVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\IssuesDomain\Model\Repository\IssueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testUnsupportedAttribute()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginAs('lucas.oconnell@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $issue));
    }

    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new IssueVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue, [IssueVoter::VIEW_ISSUE]));
    }

    public function testViewByAuthor()
    {
        [$issue1] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [$issue2] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $this->loginAs('lucas.oconnell@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue1));
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue2));
    }

    public function testViewByResponsible()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);

        $this->loginAs('nhills@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginAs('jkiehn@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    public function testViewByLocalGroup()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $this->loginAs('labshire@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginAs('jkiehn@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }

    public function testViewByGlobalGroup()
    {
        [$issue] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        $this->loginAs('labshire@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));

        $this->loginAs('clegros@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::VIEW_ISSUE, $issue));
    }
}
