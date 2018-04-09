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

namespace eTraxis\SecurityDomain\Application\Voter;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class GroupVoterTest extends TransactionalTestCase
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
        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $group));
    }

    public function testAnonymous()
    {
        $voter = new GroupVoter();
        $token = new AnonymousToken('', 'anon.');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, null, [GroupVoter::CREATE_GROUP]));
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::UPDATE_GROUP]));
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::DELETE_GROUP]));
        self::assertSame(GroupVoter::ACCESS_DENIED, $voter->vote($token, $group, [GroupVoter::MANAGE_MEMBERSHIP]));
    }

    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::CREATE_GROUP));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::CREATE_GROUP));
    }

    public function testUpdate()
    {
        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::UPDATE_GROUP, $group));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::UPDATE_GROUP, $group));
    }

    public function testDelete()
    {
        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::DELETE_GROUP, $group));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::DELETE_GROUP, $group));
    }

    public function testManageMembership()
    {
        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $repository */
        $repository = $this->doctrine->getRepository(Group::class);

        [$group] = $repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group));
    }
}
