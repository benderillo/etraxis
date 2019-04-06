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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\States;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\States\SetRolesTransitionCommand;
use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Entity\StateRoleTransition;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetRolesTransitionHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param StateRepository               $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        StateRepository               $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param SetRolesTransitionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(SetRolesTransitionCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $fromState */
        $fromState = $this->repository->find($command->from);

        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $toState */
        $toState = $this->repository->find($command->to);

        if (!$fromState || !$toState) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::MANAGE_TRANSITIONS, $fromState)) {
            throw new AccessDeniedHttpException();
        }

        // Remove all roles which are supposed to not be granted for specified transition, but they currently are.
        $transitions = array_filter($fromState->roleTransitions, function (StateRoleTransition $transition) use ($command) {
            return $transition->toState->id === $command->to;
        });

        foreach ($transitions as $transition) {
            if (!in_array($transition->role, $command->roles, true)) {
                $this->manager->remove($transition);
            }
        }

        // Add all roles which are supposed to be granted for specified transition, but they currently are not.
        $existingRoles = array_map(function (StateRoleTransition $transition) {
            return $transition->role;
        }, $transitions);

        foreach ($command->roles as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $transition = new StateRoleTransition($fromState, $toState, $role);
                $this->manager->persist($transition);
            }
        }
    }
}
