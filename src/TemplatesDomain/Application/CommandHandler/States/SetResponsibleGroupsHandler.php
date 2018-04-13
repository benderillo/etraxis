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
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Application\Command\States\SetResponsibleGroupsCommand;
use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Entity\StateResponsibleGroup;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetResponsibleGroupsHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * Dependency Injection constructor.
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
     * @param SetResponsibleGroupsCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(SetResponsibleGroupsCommand $command): void
    {
        /** @var \eTraxis\TemplatesDomain\Model\Entity\State $state */
        $state = $this->repository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $state)) {
            throw new AccessDeniedHttpException();
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'))
            ->setParameter('groups', $command->groups);

        $requestedGroups = $query->getQuery()->getResult();

        foreach ($state->responsibleGroups as $responsibleGroup) {
            if (!in_array($responsibleGroup->group, $requestedGroups, true)) {
                $this->manager->remove($responsibleGroup);
            }
        }

        $existingGroups = array_map(function (StateResponsibleGroup $responsibleGroup) {
            return $responsibleGroup->group;
        }, $state->responsibleGroups);

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $responsibleGroup = new StateResponsibleGroup($state, $group);
                $this->manager->persist($responsibleGroup);
            }
        }
    }
}
