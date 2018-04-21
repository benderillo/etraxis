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

use eTraxis\TemplatesDomain\Application\Command\States\DeleteStateCommand;
use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteStateHandler
{
    protected $security;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param StateRepository               $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, StateRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteStateCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteStateCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $state */
        $state = $this->repository->find($command->state);

        if ($state) {

            if (!$this->security->isGranted(StateVoter::DELETE_STATE, $state)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($state);
        }
    }
}
