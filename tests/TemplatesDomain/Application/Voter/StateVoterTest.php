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

    protected function setUp()
    {
        parent::setUp();

        $this->security = $this->client->getContainer()->get('security.authorization_checker');
    }

    public function testUnsupportedAttribute()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [$state] = $repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $state));
    }

    public function testAnonymous()
    {
        $voter = new StateVoter();
        $token = new AnonymousToken('', 'anon.');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [$state] = $repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $template, [StateVoter::CREATE_STATE]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::UPDATE_STATE]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::DELETE_STATE]));
        self::assertSame(StateVoter::ACCESS_DENIED, $voter->vote($token, $state, [StateVoter::SET_INITIAL]));
    }

    public function testCreate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$templateA, /* skipping */, $templateC] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::CREATE_STATE, $templateA));
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateA));
        self::assertFalse($this->security->isGranted(StateVoter::CREATE_STATE, $templateC));
    }

    public function testUpdate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [$stateA, /* skipping */, $stateC] = $repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::UPDATE_STATE, $stateA));
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateA));
        self::assertFalse($this->security->isGranted(StateVoter::UPDATE_STATE, $stateC));
    }

    public function testDelete()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [$stateA, /* skipping */, $stateC] = $repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::DELETE_STATE, $stateA));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateA));
        self::assertFalse($this->security->isGranted(StateVoter::DELETE_STATE, $stateC));
    }

    public function testSetInitial()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [$stateA, /* skipping */, $stateC] = $repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(StateVoter::SET_INITIAL, $stateA));
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateA));
        self::assertFalse($this->security->isGranted(StateVoter::SET_INITIAL, $stateC));
    }
}
