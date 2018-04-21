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
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "ListItem" entities.
 */
class ListItemVoter extends Voter
{
    use VoterTrait;

    public const CREATE_ITEM = 'listitem.create';
    public const UPDATE_ITEM = 'listitem.update';
    public const DELETE_ITEM = 'listitem.delete';

    protected $attributes = [
        self::CREATE_ITEM => Field::class,
        self::UPDATE_ITEM => ListItem::class,
        self::DELETE_ITEM => ListItem::class,
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

            case self::CREATE_ITEM:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_ITEM:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_ITEM:
                return $this->isDeleteGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new item can be created in the specified field.
     *
     * @param Field $subject Subject field.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(Field $subject, User $user): bool
    {
        return $user->isAdmin && $subject->state->template->isLocked && $subject->type === FieldType::LIST;
    }

    /**
     * Whether the specified item can be updated.
     *
     * @param ListItem $subject Subject item.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(ListItem $subject, User $user): bool
    {
        return $user->isAdmin && $subject->field->state->template->isLocked;
    }

    /**
     * Whether the specified item can be deleted.
     *
     * @param ListItem $subject Subject item.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(ListItem $subject, User $user): bool
    {
        /** @todo Can't delete item if it was used in at least one issue. */

        return $user->isAdmin && $subject->field->state->template->isLocked;
    }
}
