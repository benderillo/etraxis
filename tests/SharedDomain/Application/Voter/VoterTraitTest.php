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

namespace eTraxis\SharedDomain\Application\Voter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\User;

class VoterTraitTest extends TestCase
{
    public function testSupportedAttribute()
    {
        $user = new User('artem', 'secret');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);
        $voter = new DummyVoter();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $user, ['update']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $user, ['delete']));
    }

    public function testUnsupportedAttribute()
    {
        $user = new User('artem', 'secret');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);
        $voter = new DummyVoter();

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['unknown']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $user, ['unknown']));
    }

    public function testMissingClass()
    {
        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);
        $voter = new DummyVoter();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['update']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['delete']));
    }

    public function testWrongClass()
    {
        $user = new \stdClass();

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);
        $voter = new DummyVoter();

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $user, ['update']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $user, ['delete']));
    }
}
