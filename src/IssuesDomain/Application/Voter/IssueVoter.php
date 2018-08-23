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
use eTraxis\TemplatesDomain\Model\Entity\StateGroupTransition;
use eTraxis\TemplatesDomain\Model\Entity\StateResponsibleGroup;
use eTraxis\TemplatesDomain\Model\Entity\StateRoleTransition;
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

    public const VIEW_ISSUE        = 'issue.view';
    public const CREATE_ISSUE      = 'issue.create';
    public const UPDATE_ISSUE      = 'issue.update';
    public const DELETE_ISSUE      = 'issue.delete';
    public const CHANGE_STATE      = 'state.change';
    public const ASSIGN_ISSUE      = 'issue.assign';
    public const REASSIGN_ISSUE    = 'issue.reassign';
    public const SUSPEND_ISSUE     = 'issue.suspend';
    public const RESUME_ISSUE      = 'issue.resume';
    public const ADD_DEPENDENCY    = 'dependency.add';
    public const REMOVE_DEPENDENCY = 'dependency.remove';

    protected $attributes = [
        self::VIEW_ISSUE        => Issue::class,
        self::CREATE_ISSUE      => Template::class,
        self::UPDATE_ISSUE      => Issue::class,
        self::DELETE_ISSUE      => Issue::class,
        self::CHANGE_STATE      => [Issue::class, State::class],
        self::ASSIGN_ISSUE      => [State::class, User::class],
        self::REASSIGN_ISSUE    => [Issue::class, User::class],
        self::SUSPEND_ISSUE     => Issue::class,
        self::RESUME_ISSUE      => Issue::class,
        self::ADD_DEPENDENCY    => Issue::class,
        self::REMOVE_DEPENDENCY => Issue::class,
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

            case self::DELETE_ISSUE:
                return $this->isDeleteGranted($subject, $user);

            case self::CHANGE_STATE:
                return $this->isChangeStateGranted($subject[0], $subject[1], $user);

            case self::ASSIGN_ISSUE:
                return $this->isAssignGranted($subject[0], $subject[1], $user);

            case self::REASSIGN_ISSUE:
                return $this->isReassignGranted($subject[0], $subject[1], $user);

            case self::SUSPEND_ISSUE:
                return $this->isSuspendGranted($subject, $user);

            case self::RESUME_ISSUE:
                return $this->isResumeGranted($subject, $user);

            case self::ADD_DEPENDENCY:
                return $this->isAddDependencyGranted($subject, $user);

            case self::REMOVE_DEPENDENCY:
                return $this->isRemoveDependencyGranted($subject, $user);

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
        if ($subject->responsible === $user) {
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
        // Issue must not be suspended or frozen.
        if ($subject->isSuspended || $subject->isFrozen) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::EDIT_ISSUES);
    }

    /**
     * Whether the specified issue can be deleted.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isDeleteGranted(Issue $subject, User $user): bool
    {
        // Issue must not be suspended.
        if ($subject->isSuspended) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::DELETE_ISSUES);
    }

    /**
     * Whether the current state of the specified issue can be changed to the specified state.
     *
     * @param Issue $subject Subject issue.
     * @param State $state   New state of the issue.
     * @param User  $user    Current user.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    protected function isChangeStateGranted(Issue $subject, State $state, User $user): bool
    {
        // Issue must not be suspended or frozen.
        if ($subject->isSuspended || $subject->isFrozen) {
            return false;
        }

        // Template must not be locked and project must not be suspended.
        if ($subject->template->isLocked || $subject->project->isSuspended) {
            return false;
        }

        // Check whether the issue has opened dependencies.
        foreach ($subject->dependencies as $dependency) {
            if (!$dependency->isClosed) {
                return false;
            }
        }

        // Check whether the user has required permissions by role.
        $roles = [SystemRole::ANYONE];

        if ($subject->author === $user) {
            $roles[] = SystemRole::AUTHOR;
        }

        if ($subject->responsible === $user) {
            $roles[] = SystemRole::RESPONSIBLE;
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(st.role)')
            ->from(StateRoleTransition::class, 'st')
            ->where('st.fromState = :from')
            ->andWhere('st.toState = :to')
            ->andWhere($query->expr()->in('st.role', ':roles'))
            ->setParameters([
                'from'  => $subject->state,
                'to'    => $state,
                'roles' => $roles,
            ]);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        if ($result !== 0) {
            return true;
        }

        // Check whether the user has required permissions by group.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(st.group)')
            ->from(StateGroupTransition::class, 'st')
            ->where('st.fromState = :from')
            ->andWhere('st.toState = :to')
            ->andWhere($query->expr()->in('st.group', ':groups'))
            ->setParameters([
                'from'   => $subject->state,
                'to'     => $state,
                'groups' => $user->groups,
            ]);

        $result = (int) $query->getQuery()->getSingleScalarResult();

        if ($result !== 0) {
            return true;
        }

        return false;
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
     * Whether the specified user can reassign specified issue to another specified user.
     *
     * @param Issue $subject  Subject issue.
     * @param User  $assignee User to be assigned.
     * @param User  $user     Current user.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    protected function isReassignGranted(Issue $subject, User $assignee, User $user): bool
    {
        // Issue must not be suspended or closed.
        if ($subject->isSuspended || $subject->isClosed) {
            return false;
        }

        // Issue must be assigned.
        if ($subject->responsible === null) {
            return false;
        }

        // Issue must be assignable to the specified user.
        if (!$this->isAssignGranted($subject->state, $assignee, $user)) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::REASSIGN_ISSUES);
    }

    /**
     * Whether the specified issue can be suspended.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isSuspendGranted(Issue $subject, User $user): bool
    {
        // Issue must not be suspended or closed.
        if ($subject->isSuspended || $subject->isClosed) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::SUSPEND_ISSUES);
    }

    /**
     * Whether the specified issue can be resumed.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isResumeGranted(Issue $subject, User $user): bool
    {
        // Issue must not be suspended or closed.
        if (!$subject->isSuspended || $subject->isClosed) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::RESUME_ISSUES);
    }

    /**
     * Whether a dependency can be added to the specified issue.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isAddDependencyGranted(Issue $subject, User $user): bool
    {
        // Issue must not be suspended or closed.
        if ($subject->isSuspended || $subject->isClosed) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::ADD_DEPENDENCIES);
    }

    /**
     * Whether a dependency can be removed from the specified issue.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isRemoveDependencyGranted(Issue $subject, User $user): bool
    {
        // Issue must not be suspended or closed.
        if ($subject->isSuspended || $subject->isClosed) {
            return false;
        }

        return $this->hasPermission($subject, $user, TemplatePermission::REMOVE_DEPENDENCIES);
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
                ->where('tp.template = :template');

            $this->rolesCache[$template->id] = $query->getQuery()->execute([
                'template' => $template,
            ]);
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
                ->andWhere($query->expr()->in('tp.group', ':groups'));

            $this->groupsCache[$key] = $query->getQuery()->execute([
                'template' => $template,
                'groups'   => $user->groups,
            ]);
        }

        return in_array(['permission' => $permission], $this->groupsCache[$key], true);
    }

    /**
     * Checks whether the specified user is granted to specified permission for the issue either by group or by role.
     *
     * @param Issue  $issue      Issue.
     * @param User   $user       User.
     * @param string $permission Permission.
     *
     * @return bool
     */
    private function hasPermission(Issue $issue, User $user, string $permission): bool
    {
        // Template must not be locked and project must not be suspended.
        if ($issue->template->isLocked || $issue->project->isSuspended) {
            return false;
        }

        // Check whether the user has required permissions as author.
        if ($issue->author === $user && $this->hasRolePermission($issue->template, SystemRole::AUTHOR, $permission)) {
            return true;
        }

        // Check whether the user has required permissions as current responsible.
        if ($issue->responsible === $user && $this->hasRolePermission($issue->template, SystemRole::RESPONSIBLE, $permission)) {
            return true;
        }

        return
            $this->hasRolePermission($issue->template, SystemRole::ANYONE, $permission) ||
            $this->hasGroupPermission($issue->template, $user, $permission);
    }
}
