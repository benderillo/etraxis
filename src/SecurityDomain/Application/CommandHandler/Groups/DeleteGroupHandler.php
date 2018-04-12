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

use eTraxis\SecurityDomain\Application\Command\Groups\DeleteGroupCommand;
use eTraxis\SecurityDomain\Application\Voter\GroupVoter;
use eTraxis\SecurityDomain\Model\Repository\GroupRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteGroupHandler
{
    protected $security;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param GroupRepository               $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, GroupRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteGroupCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteGroupCommand $command): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\Group $group */
        $group = $this->repository->find($command->group);

        if ($group) {

            if (!$this->security->isGranted(GroupVoter::DELETE_GROUP, $group)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($group);
        }
    }
}
