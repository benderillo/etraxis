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

use eTraxis\TemplatesDomain\Application\Command\States\UpdateStateCommand;
use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateStateHandler
{
    protected $security;
    protected $validator;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param StateRepository               $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        StateRepository               $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UpdateStateCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $state */
        $state = $this->repository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::UPDATE_STATE, $state)) {
            throw new AccessDeniedHttpException();
        }

        $state->name        = $command->name;
        $state->responsible = $command->responsible;

        if ($command->next) {

            /** @var null|State $nextState */
            $nextState = $this->repository->find($command->next);

            if (!$nextState || $nextState->template !== $state->template) {
                throw new NotFoundHttpException('Unknown next state.');
            }

            $state->nextState = $nextState;
        }

        $errors = $this->validator->validate($state);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($state);
    }
}
