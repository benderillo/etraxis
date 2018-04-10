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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "User" entities.
 */
class UserVoter extends Voter
{
    public const CREATE_USER  = 'user.create';
    public const UPDATE_USER  = 'user.update';
    public const DELETE_USER  = 'user.delete';
    public const DISABLE_USER = 'user.disable';
    public const ENABLE_USER  = 'user.enable';
    public const UNLOCK_USER  = 'user.unlock';
    public const SET_PASSWORD = 'user.password';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = [
            self::CREATE_USER  => null,
            self::UPDATE_USER  => User::class,
            self::DELETE_USER  => User::class,
            self::DISABLE_USER => User::class,
            self::ENABLE_USER  => User::class,
            self::UNLOCK_USER  => User::class,
            self::SET_PASSWORD => User::class,
        ];

        // Whether the attribute is supported.
        if (!array_key_exists($attribute, $attributes)) {
            return false;
        }

        // Whether the subject is not required.
        if ($attributes[$attribute] === null) {
            return true;
        }

        // The subject must be an object of expected class.
        return is_object($subject) && get_class($subject) === $attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // User must be logged in.
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {

            case self::CREATE_USER:
                return $this->isCreateGranted($user);

            case self::UPDATE_USER:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_USER:
                return $this->isDeleteGranted($subject, $user);

            case self::DISABLE_USER:
                return $this->isDisableGranted($subject, $user);

            case self::ENABLE_USER:
                return $this->isEnableGranted($subject, $user);

            case self::UNLOCK_USER:
                return $this->isUnlockGranted($subject, $user);

            case self::SET_PASSWORD:
                return $this->isSetPasswordGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether the current user can create a new one.
     *
     * @param User $user Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be updated.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(User $subject, User $user): bool
    {
        return $user->isAdmin || $subject->id === $user->id;
    }

    /**
     * Whether the specified user can be deleted.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(User $subject, User $user): bool
    {
        // Can't delete oneself.
        if ($subject->id === $user->id) {
            return false;
        }

        /** @todo Can't delete user if mentioned in an issue history. */

        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be disabled.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isDisableGranted(User $subject, User $user): bool
    {
        // Can't disable oneself.
        if ($subject->id === $user->id) {
            return false;
        }

        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be enabled.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isEnableGranted(User $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified user can be unlocked.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isUnlockGranted(User $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether a password of the specified user can be set.
     *
     * @param User $subject Subject user.
     * @param User $user    Current user.
     *
     * @return bool
     */
    protected function isSetPasswordGranted(User $subject, User $user): bool
    {
        // Can't set password of an external account.
        if ($subject->isAccountExternal()) {
            return false;
        }

        return $user->isAdmin || $subject->id === $user->id;
    }
}
