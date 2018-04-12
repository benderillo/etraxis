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
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "Template" entities.
 */
class TemplateVoter extends Voter
{
    use VoterTrait;

    public const CREATE_TEMPLATE    = 'template.create';
    public const UPDATE_TEMPLATE    = 'template.update';
    public const DELETE_TEMPLATE    = 'template.delete';
    public const LOCK_TEMPLATE      = 'template.lock';
    public const UNLOCK_TEMPLATE    = 'template.unlock';
    public const MANAGE_PERMISSIONS = 'template.permissions';

    protected $attributes = [
        self::CREATE_TEMPLATE    => Project::class,
        self::UPDATE_TEMPLATE    => Template::class,
        self::DELETE_TEMPLATE    => Template::class,
        self::LOCK_TEMPLATE      => Template::class,
        self::UNLOCK_TEMPLATE    => Template::class,
        self::MANAGE_PERMISSIONS => Template::class,
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

            case self::CREATE_TEMPLATE:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_TEMPLATE:
                return $this->isUpdateGranted($subject, $user);

            case self::DELETE_TEMPLATE:
                return $this->isDeleteGranted($subject, $user);

            case self::LOCK_TEMPLATE:
                return $this->isLockGranted($subject, $user);

            case self::UNLOCK_TEMPLATE:
                return $this->isUnlockGranted($subject, $user);

            case self::MANAGE_PERMISSIONS:
                return $this->isManagePermissionsGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether a new template can be created in the specified project.
     *
     * @param Project $project Subject project.
     * @param User    $user    Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(Project $project, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified template can be updated.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified template can be deleted.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(Template $subject, User $user): bool
    {
        /** @todo Can't delete template if at least one issue is created using it. */

        return $user->isAdmin;
    }

    /**
     * Whether the specified template can be locked.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isLockGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether the specified template can be unlocked.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isUnlockGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }

    /**
     * Whether permissions of the specified template can be changed.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isManagePermissionsGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin;
    }
}
