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

namespace eTraxis\SecurityDomain\Application\CommandHandler\Groups;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\SecurityDomain\Application\Command\Groups\RemoveMembersCommand;
use eTraxis\SecurityDomain\Application\Voter\GroupVoter;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SecurityDomain\Model\Repository\GroupRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class RemoveMembersHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param GroupRepository               $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        GroupRepository               $repository,
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
     * @param RemoveMembersCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(RemoveMembersCommand $command): void
    {
        /** @var null|\eTraxis\SecurityDomain\Model\Entity\Group $group */
        $group = $this->repository->find($command->group);

        if (!$group) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group)) {
            throw new AccessDeniedHttpException();
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('user')
            ->from(User::class, 'user')
            ->where($query->expr()->in('user.id', ':users'));

        /** @var User[] $users */
        $users = $query->getQuery()->execute([
            'users' => $command->users,
        ]);

        foreach ($users as $user) {
            $group->removeMember($user);
        }

        $this->repository->persist($group);
    }
}
