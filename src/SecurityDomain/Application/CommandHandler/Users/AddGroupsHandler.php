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

namespace eTraxis\SecurityDomain\Application\CommandHandler\Users;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\SecurityDomain\Application\Command\Users\AddGroupsCommand;
use eTraxis\SecurityDomain\Application\Voter\GroupVoter;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Repository\GroupRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class AddGroupsHandler
{
    protected $security;
    protected $userRepository;
    protected $groupRepository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepository                $userRepository
     * @param GroupRepository               $groupRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        UserRepository                $userRepository,
        GroupRepository               $groupRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security        = $security;
        $this->userRepository  = $userRepository;
        $this->groupRepository = $groupRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param AddGroupsCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(AddGroupsCommand $command): void
    {
        /** @var null|\eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->userRepository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'))
            ->setParameter('groups', $command->groups);

        /** @var Group[] $groups */
        $groups = $query->getQuery()->getResult();

        foreach ($groups as $group) {

            if (!$this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group)) {
                throw new AccessDeniedHttpException();
            }

            $group->addMember($user);
            $this->groupRepository->persist($group);
        }
    }
}
