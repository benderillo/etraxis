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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\User;

class DummyVoter extends Voter
{
    use VoterTrait;

    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';

    protected $attributes = [
        self::CREATE => null,
        self::UPDATE => User::class,
        self::DELETE => User::class,
    ];

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        return true;
    }
}
