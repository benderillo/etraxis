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
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
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

        $developer  = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [$state]    = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        [$issue1] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [$issue2] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);
        [$issue5] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);
        [$issue6] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::VIEW_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $template, [IssueVoter::CREATE_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::UPDATE_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue1, [IssueVoter::DELETE_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, [$issue6, $state], [IssueVoter::CHANGE_STATE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, [$state, $developer], [IssueVoter::ASSIGN_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, [$issue2, $developer], [IssueVoter::REASSIGN_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue6, [IssueVoter::SUSPEND_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue5, [IssueVoter::RESUME_ISSUE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ADD_PUBLIC_COMMENT]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ADD_PRIVATE_COMMENT]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::READ_PRIVATE_COMMENT]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ATTACH_FILE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::DELETE_FILE]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::ADD_DEPENDENCY]));
        self::assertSame(IssueVoter::ACCESS_DENIED, $voter->vote($token, $issue2, [IssueVoter::REMOVE_DEPENDENCY]));
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

    public function testCreate()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$templateA, $templateB, $templateC] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        // Template D doesn't have initial state.
        [$templateD] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'DESC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateA));
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateB));
        self::assertTrue($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateC));
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateD));

        $this->loginAs('lucas.oconnell@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CREATE_ISSUE, $templateC));
    }

    public function testUpdate()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $assignedToDev3));

        /** @var Issue $issueC */
        $issueC->template->frozenTime = 1;

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::UPDATE_ISSUE, $issueC));
    }

    public function testDelete()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $assignedToDev3));

        /** @var Issue $issueC */
        $issueC->template->frozenTime = 1;

        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_ISSUE, $issueC));
    }

    public function testChangeState()
    {
        [$stateA, $stateB, $stateC] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Resolved'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $reopen] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Opened'], ['id' => 'ASC']);

        // Template B is locked, template C is not.
        // Project A is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Support request 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $dependant] = $this->repository->findBy(['subject' => 'Support request 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByClient1]   = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $createdByClient3]   = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToSupport1] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$issueA, $stateA]));
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$issueB, $stateB]));
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, [$issueC, $stateC]));
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$suspended, $stateC]));
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$dependant, $reopen]));

        $this->loginAs('dtillman@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$issueC, $stateC]));
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, [$createdByClient3, $stateC]));

        $this->loginAs('cbatz@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$issueC, $stateC]));
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, [$assignedToSupport1, $stateC]));

        $this->loginAs('lucas.oconnell@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$issueC, $stateC]));
        self::assertFalse($this->security->isGranted(IssueVoter::CHANGE_STATE, [$createdByClient1, $reopen]));

        /** @var Issue $issueC */
        $issueC->template->frozenTime = null;
        self::assertTrue($this->security->isGranted(IssueVoter::CHANGE_STATE, [$createdByClient1, $reopen]));
    }

    public function testAssign()
    {
        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $developer = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);
        $support   = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::ASSIGN_ISSUE, [$state, $developer]));
        self::assertFalse($this->security->isGranted(IssueVoter::ASSIGN_ISSUE, [$state, $support]));
    }

    public function testReassign()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $unassigned] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $developer = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);
        $support   = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issueA, $developer]));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issueB, $developer]));
        self::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issueC, $developer]));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issueC, $support]));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$unassigned, $developer]));

        $this->loginAs('dquigley@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$createdByDev2, $developer]));
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$assignedToDev3, $developer]));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$createdByDev2, $developer]));
        self::assertTrue($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$assignedToDev3, $developer]));

        /** @var Issue $issueC */
        $issueC->suspend(time() + 86400);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issueC, $developer]));
    }

    public function testSuspend()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $suspended));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $closed));

        $this->loginAs('dquigley@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $assignedToDev3));
    }

    public function testResume()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $resumed] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]  = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $createdByDev2 */
        $createdByDev2->suspend(time() + 86400);

        /** @var Issue $assignedToDev3 */
        $assignedToDev3->suspend(time() + 86400);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $resumed));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $closed));

        $this->loginAs('dquigley@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::RESUME_ISSUE, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::RESUME_ISSUE, $assignedToDev3));
    }

    public function testAddPublicComment()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $closed));
        $closed->template->frozenTime = 1;
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $closed));
    }

    public function testAddPrivateComment()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $closed));
        $closed->template->frozenTime = 1;
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $closed));
    }

    public function testReadPrivateComment()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueA));
        self::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::READ_PRIVATE_COMMENT, $assignedToDev3));
    }

    public function testAttachFile()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::ATTACH_FILE, $closed));
        $closed->template->frozenTime = 1;
        self::assertFalse($this->security->isGranted(IssueVoter::ATTACH_FILE, $closed));
    }

    public function testDeleteFile()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev3]  = $this->repository->findBy(['subject' => 'Development task 3'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $suspended));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $createdByDev3));
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $assignedToDev3));

        /** @var Issue $closed */
        $this->loginAs('ldoyle@example.com');
        self::assertTrue($this->security->isGranted(IssueVoter::DELETE_FILE, $closed));
        $closed->template->frozenTime = 1;
        self::assertFalse($this->security->isGranted(IssueVoter::DELETE_FILE, $closed));
    }

    public function testAddDependency()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $suspended));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $closed));

        $this->loginAs('dquigley@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::ADD_DEPENDENCY, $assignedToDev3));
    }

    public function testRemoveDependency()
    {
        // Template B is locked, template C is not.
        // Template A is not locked, too, but the project is suspended.
        [$issueA, $issueB, $issueC] = $this->repository->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $suspended] = $this->repository->findBy(['subject' => 'Development task 5'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $closed]    = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        [/* skipping */, /* skipping */, $createdByDev2]  = $this->repository->findBy(['subject' => 'Development task 8'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $assignedToDev3] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $this->loginAs('ldoyle@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueA));
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueB));
        self::assertTrue($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $suspended));
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $closed));

        $this->loginAs('dquigley@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueC));
        self::assertTrue($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $createdByDev2));
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $assignedToDev3));

        $this->loginAs('akoepp@example.com');
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $issueC));
        self::assertFalse($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $createdByDev2));
        self::assertTrue($this->security->isGranted(IssueVoter::REMOVE_DEPENDENCY, $assignedToDev3));
    }
}
