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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class UserVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testUnsupportedAttribute()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $nhills));
    }

    public function testAnonymous()
    {
        $voter = new UserVoter();
        $token = new AnonymousToken('', 'anon.');

        $nhills = $this->repository->findOneByUsername('nhills@example.com');

        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, null, [UserVoter::CREATE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UPDATE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DELETE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::DISABLE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::ENABLE_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::UNLOCK_USER]));
        self::assertSame(UserVoter::ACCESS_DENIED, $voter->vote($token, $nhills, [UserVoter::SET_PASSWORD]));
    }

    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::CREATE_USER));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::CREATE_USER));
    }

    public function testUpdate()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $artem  = $this->repository->findOneByUsername('artem@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::UPDATE_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::UPDATE_USER, $artem));
    }

    public function testDelete()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $admin  = $this->repository->findOneByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::DELETE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $admin));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::DELETE_USER, $nhills));
    }

    public function testDisable()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $tberge = $this->repository->findOneByUsername('tberge@example.com');
        $admin  = $this->repository->findOneByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $admin));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::DISABLE_USER, $tberge));
    }

    public function testEnable()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $tberge = $this->repository->findOneByUsername('tberge@example.com');
        $admin  = $this->repository->findOneByUsername('admin@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
        self::assertTrue($this->security->isGranted(UserVoter::ENABLE_USER, $admin));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::ENABLE_USER, $tberge));
    }

    public function testUnlock()
    {
        $nhills = $this->repository->findOneByUsername('nhills@example.com');
        $zapp   = $this->repository->findOneByUsername('jgutmann@example.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::UNLOCK_USER, $nhills));
        self::assertTrue($this->security->isGranted(UserVoter::UNLOCK_USER, $zapp));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::UNLOCK_USER, $zapp));
    }

    public function testSetPassword()
    {
        $nhills   = $this->repository->findOneByUsername('nhills@example.com');
        $einstein = $this->repository->findOneByUsername('einstein@ldap.forumsys.com');

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginAs('nhills@example.com');
        self::assertTrue($this->security->isGranted(UserVoter::SET_PASSWORD, $nhills));

        $this->loginAs('einstein@ldap.forumsys.com');
        self::assertFalse($this->security->isGranted(UserVoter::SET_PASSWORD, $einstein));
    }
}
