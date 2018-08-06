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

namespace eTraxis\IssuesDomain\Application\Voter;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SharedDomain\Application\Voter\VoterTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StateResponsibleGroup;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Entity\TemplateGroupPermission;
use eTraxis\TemplatesDomain\Model\Entity\TemplateRolePermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "Issue" entities.
 */
class IssueVoter extends Voter
{
    use VoterTrait;

    public const VIEW_ISSUE   = 'issue.view';
    public const CREATE_ISSUE = 'issue.create';
    public const UPDATE_ISSUE = 'issue.update';
    public const ASSIGN_ISSUE = 'issue.assign';

    protected $attributes = [
        self::VIEW_ISSUE   => Issue::class,
        self::CREATE_ISSUE => Template::class,
        self::UPDATE_ISSUE => Issue::class,
        self::ASSIGN_ISSUE => [State::class, User::class],
    ];

    protected $manager;

    private $rolesCache  = [];
    private $groupsCache = [];

    /**
     * Dependency Injection constructor.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
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

            case self::VIEW_ISSUE:
                return $this->isViewGranted($subject, $user);

            case self::CREATE_ISSUE:
                return $this->isCreateGranted($subject, $user);

            case self::UPDATE_ISSUE:
                return $this->isUpdateGranted($subject, $user);

            case self::ASSIGN_ISSUE:
                return $this->isAssignGranted($subject[0], $subject[1], $user);

            default:
                return false;
        }
    }

    /**
     * Whether the specified issue can be viewed.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isViewGranted(Issue $subject, User $user): bool
    {
        // Authors can always view their issues.
        if ($subject->author === $user) {
            return true;
        }

        // Responsibles can always view their issues.
        if ($subject->responsible !== null && $subject->responsible === $user) {
            return true;
        }

        return $this->hasGroupPermission($subject->template, $user, TemplatePermission::VIEW_ISSUES);
    }

    /**
     * Whether a new issue can be created using the specified template.
     *
     * @param Template $subject Subject template.
     * @param User     $user    Current user.
     *
     * @return bool
     */
    protected function isCreateGranted(Template $subject, User $user): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($subject->isLocked || $subject->project->isSuspended) {
            return false;
        }

        // One of the states must be set as initial.
        if ($subject->initialState === null) {
            return false;
        }

        return
            $this->hasRolePermission($subject, SystemRole::ANYONE, TemplatePermission::CREATE_ISSUES) ||
            $this->hasGroupPermission($subject, $user, TemplatePermission::CREATE_ISSUES);
    }

    /**
     * Whether the specified issue can be updated.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isUpdateGranted(Issue $subject, User $user): bool
    {
        // Issue must not be suspended.
        if ($subject->isSuspended || $subject->isFrozen) {
            return false;
        }

        // Template must not be locked and project must not be suspended.
        if ($subject->template->isLocked || $subject->project->isSuspended) {
            return false;
        }

        // Check whether the user has required permissions as author.
        if ($subject->author === $user && $this->hasRolePermission($subject->template, SystemRole::AUTHOR, TemplatePermission::EDIT_ISSUES)) {
            return true;
        }

        // Check whether the user has required permissions as current responsible.
        if ($subject->responsible === $user && $this->hasRolePermission($subject->template, SystemRole::RESPONSIBLE, TemplatePermission::EDIT_ISSUES)) {
            return true;
        }

        return
            $this->hasRolePermission($subject->template, SystemRole::ANYONE, TemplatePermission::EDIT_ISSUES) ||
            $this->hasGroupPermission($subject->template, $user, TemplatePermission::EDIT_ISSUES);
    }

    /**
     * Whether the specified user can be assigned to an issue if it's in the specified state.
     *
     * @param State $subject  Subject state.
     * @param User  $assignee User to be assigned.
     * @param User  $user     Current user.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    protected function isAssignGranted(State $subject, User $assignee, User $user): bool
    {
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(sr.group)')
            ->from(StateResponsibleGroup::class, 'sr')
            ->where('sr.state = :state')
            ->andWhere($query->expr()->in('sr.group', ':groups'))
            ->setParameter('state', $subject)
            ->setParameter('groups', $assignee->groups);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return $result !== 0;
    }

    /**
     * Checks whether the specified system role is granted to specified permission for the template.
     *
     * @param Template $template   Template.
     * @param string   $role       System role (see the "SystemRole" dictionary).
     * @param string   $permission Permission.
     *
     * @return bool
     */
    private function hasRolePermission(Template $template, string $role, string $permission): bool
    {
        // If we don't have the permissions info yet, retrieve it from the DB and cache to reuse.
        if (!array_key_exists($template->id, $this->rolesCache)) {

            $query = $this->manager->createQueryBuilder();

            $query
                ->distinct()
                ->select('tp.role')
                ->addSelect('tp.permission')
                ->from(TemplateRolePermission::class, 'tp')
                ->where('tp.template = :template')
                ->setParameter('template', $template);

            $this->rolesCache[$template->id] = $query->getQuery()->getResult();
        }

        return in_array(['role' => $role, 'permission' => $permission], $this->rolesCache[$template->id], true);
    }

    /**
     * Checks whether the specified user is granted to specified group permission for the template.
     *
     * @param Template $template   Template.
     * @param User     $user       User.
     * @param string   $permission Permission.
     *
     * @return bool
     */
    private function hasGroupPermission(Template $template, User $user, string $permission): bool
    {
        $key = sprintf('%s:%s', $template->id, $user->id);

        // If we don't have the permissions info yet, retrieve it from the DB and cache to reuse.
        if (!array_key_exists($key, $this->groupsCache)) {

            $query = $this->manager->createQueryBuilder();

            $query
                ->distinct()
                ->select('tp.permission')
                ->from(TemplateGroupPermission::class, 'tp')
                ->where('tp.template = :template')
                ->andWhere($query->expr()->in('tp.group', ':groups'))
                ->setParameter('template', $template)
                ->setParameter('groups', $user->groups);

            $this->groupsCache[$key] = $query->getQuery()->getResult();
        }

        return in_array(['permission' => $permission], $this->groupsCache[$key], true);
    }
}
