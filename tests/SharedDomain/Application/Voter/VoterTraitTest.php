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
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\User;

/**
 * @coversDefaultClass \eTraxis\SharedDomain\Application\Voter\VoterTrait
 */
class VoterTraitTest extends TestCase
{
    /** @var Voter */
    protected $voter;

    protected function setUp()
    {
        parent::setUp();

        $this->voter = new class() extends Voter {
            use VoterTrait;

            protected $attributes = [
                'create' => null,
                'update' => User::class,
                'delete' => [User::class, Role::class],
            ];

            protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
            {
                return true;
            }
        };
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testSupportedAttribute()
    {
        $user = new User('artem', 'secret');
        $role = new Role('user');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $user, ['update']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, [$user, $role], ['delete']));
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testUnsupportedAttribute()
    {
        $user = new User('artem', 'secret');
        $role = new Role('user');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['unknown']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $user, ['unknown']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$user, $role], ['unknown']));
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testMissingClass()
    {
        $user = new User('artem', 'secret');
        $role = new Role('user');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['create']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['update']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, null, ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$user], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$role], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$user, null], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [null, $role], ['delete']));
    }

    /**
     * @covers ::isValid
     * @covers ::supports
     */
    public function testWrongClass()
    {
        $user = new User('artem', 'secret');
        $role = new Role('user');

        /** @var TokenInterface $token */
        $token = self::createMock(TokenInterface::class);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), ['update']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$user, new \stdClass()], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [new \stdClass(), $role], ['delete']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, [$role, $user], ['delete']));
    }
}
