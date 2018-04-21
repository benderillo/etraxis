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

use eTraxis\SecurityDomain\Application\Command\Users\UnlockUserCommand;
use eTraxis\SecurityDomain\Application\Voter\UserVoter;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class UnlockUserHandler
{
    protected $security;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param UserRepository                $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, UserRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UnlockUserCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UnlockUserCommand $command): void
    {
        /** @var null|\eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->repository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(UserVoter::UNLOCK_USER, $user)) {
            throw new AccessDeniedHttpException();
        }

        if (!$user->isAccountNonLocked()) {

            $user->unlockAccount();

            $this->repository->persist($user);
        }
    }
}
