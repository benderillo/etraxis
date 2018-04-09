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
use eTraxis\SecurityDomain\Model\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "Group" entities.
 */
class GroupVoter extends Voter
{
    public const CREATE_GROUP      = 'group.create';
    public const UPDATE_GROUP      = 'group.update';
    public const DELETE_GROUP      = 'group.delete';
    public const MANAGE_MEMBERSHIP = 'group.membership';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = [
            self::CREATE_GROUP      => null,
            self::UPDATE_GROUP      => Group::class,
            self::DELETE_GROUP      => Group::class,
            self::MANAGE_MEMBERSHIP => Group::class,
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

        /** @var Group $subject */
        switch ($attribute) {

            case self::CREATE_GROUP:
                return $this->isCreateGranted($user);

            case self::UPDATE_GROUP:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_GROUP:
                return $this->isDeleteGranted($subject, $user);

            case self::MANAGE_MEMBERSHIP:
                return $this->isManageMembershipGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new group can be created in the specified project.
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
     * Whether the specified group can be updated.
     *
     * @param Group $subject Subject group.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified group can be deleted.
     *
     * @param Group $subject Subject group.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether list of members of the specified group can be managed.
     *
     * @param Group $subject Subject group.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isManageMembershipGranted(Group $subject, User $user): bool
    {
        return $user->isAdmin;
    }
}
