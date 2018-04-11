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
use eTraxis\TemplatesDomain\Application\Command\States\CreateStateCommand;
use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateStateHandler
{
    protected $validator;
    protected $security;
    protected $templateRepository;
    protected $stateRepository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TemplateRepository            $templateRepository
     * @param StateRepository               $stateRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TemplateRepository            $templateRepository,
        StateRepository               $stateRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security           = $security;
        $this->validator          = $validator;
        $this->templateRepository = $templateRepository;
        $this->stateRepository    = $stateRepository;
        $this->manager            = $manager;
    }

    /**
     * Command handler.
     *
     * @param CreateStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return State
     */
    public function handle(CreateStateCommand $command): State
    {
        /** @var \eTraxis\TemplatesDomain\Model\Entity\Template $template */
        $template = $this->templateRepository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(StateVoter::CREATE_STATE, $template)) {
            throw new AccessDeniedHttpException();
        }

        $state = new State($template, $command->type);

        $state->name        = $command->name;
        $state->responsible = $command->responsible;

        if ($command->nextState) {

            /** @var State $nextState */
            $nextState = $this->stateRepository->find($command->nextState);

            if (!$nextState || $nextState->template->id !== $command->template) {
                throw new NotFoundHttpException('Unknown next state.');
            }

            $state->nextState = $nextState;
        }

        $errors = $this->validator->validate($state);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        // Only one initial state is allowed per template.
        if ($command->type === StateType::INITIAL) {

            $query = $this->manager->createQuery('
                UPDATE TemplatesDomain:State state
                SET state.type = :interim
                WHERE state.template = :template AND state.type = :initial
            ');

            $query->execute([
                'template' => $template,
                'initial'  => StateType::INITIAL,
                'interim'  => StateType::INTERMEDIATE,
            ]);
        }

        $this->stateRepository->persist($state);

        return $state;
    }
}
