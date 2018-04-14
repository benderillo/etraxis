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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SharedDomain\Application\Voter\VoterTrait;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\State;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "Field" entities.
 */
class FieldVoter extends Voter
{
    use VoterTrait;

    public const CREATE_FIELD              = 'field.create';
    public const UPDATE_FIELD              = 'field.update';
    public const DELETE_FIELD              = 'field.delete';
    public const MANAGE_PERMISSIONS        = 'field.permissions';

    protected $attributes = [
        self::CREATE_FIELD              => State::class,
        self::UPDATE_FIELD              => Field::class,
        self::DELETE_FIELD              => Field::class,
        self::MANAGE_PERMISSIONS        => Field::class,
    ];

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

            case self::CREATE_FIELD:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_FIELD:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_FIELD:
                return $this->isDeleteGranted($subject, $user);

            case self::MANAGE_PERMISSIONS:
                return $this->isManagePermissionsGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new field can be created in the specified state.
     *
     * @param State $subject Subject state.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(State $subject, User $user): bool
    {
        return $user->isAdmin && $subject->template->isLocked;
    }

    /**
     * Whether the specified field can be updated.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked;
    }

    /**
     * Whether the specified field can be deleted.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked;
    }

    /**
     * Whether transitions of the specified field can be changed.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isManagePermissionsGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked;
    }
}
