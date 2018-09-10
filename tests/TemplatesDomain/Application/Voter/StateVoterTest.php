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

namespace eTraxis\TemplatesDomain\Application\Voter;

use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class StateVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testUnsupportedAttribute()
    {
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $state));
    }

    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new StateVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        [/* skipping */, $state] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $template, [StateVoter::CREATE_STATE]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::UPDATE_STATE]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::DELETE_STATE]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_INITIAL]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::MANAGE_TRANSITIONS]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::MANAGE_RESPONSIBLE_GROUPS]));
    }

    public function testCreate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [/* skipping */, $templateB, $templateC] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::CREATE_STATE, $templateB));
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateB));
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));
    }

    public function testUpdate()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::UPDATE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));
    }

    public function testDelete()
    {
        [/* skipping */, $stateB, $stateC, $stateD] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
        self::assertTrue($this->security->isGranted(StateVoter::DELETE_STATE, $stateD));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateD));
    }

    public function testSetInitial()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::SET_INITIAL, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateC));
    }

    public function testManageTransitions()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateC));

        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $stateC));
    }

    public function testManageResponsibleGroups()
    {
        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateC));

        [/* skipping */, $stateB, $stateC] = $this->repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateB));
        self::assertFalse($this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $stateC));
    }
}
