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

namespace eTraxis\IssuesDomain\Application\CommandHandler;

use eTraxis\IssuesDomain\Application\Command\ChangeStateCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\FieldValueRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class ChangeStateHandler extends AbstractIssueHandler
{
    protected $stateRepository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FieldRepository               $fieldRepository
     * @param FieldValueRepository          $valueRepository
     * @param StateRepository               $stateRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FieldRepository               $fieldRepository,
        FieldValueRepository          $valueRepository,
        StateRepository               $stateRepository
    )
    {
        parent::__construct($security, $validator, $tokens, $userRepository, $issueRepository, $eventRepository, $fieldRepository, $valueRepository);

        $this->stateRepository = $stateRepository;
    }

    /**
     * Command handler.
     *
     * @param ChangeStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(ChangeStateCommand $command): void
    {
        /** @var null|\eTraxis\IssuesDomain\Model\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $state */
        $state = $this->stateRepository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(IssueVoter::CHANGE_STATE, [$issue, $state])) {
            throw new AccessDeniedHttpException('You are not allowed to change the state.');
        }

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        if (!$issue->isClosed && $state->isFinal) {
            $eventType = EventType::ISSUE_CLOSED;
        }
        elseif ($issue->isClosed && !$state->isFinal) {
            $eventType = EventType::ISSUE_REOPENED;
        }
        else {
            $eventType = EventType::STATE_CHANGED;
        }

        $issue->state = $state;

        $event = new Event($eventType, $issue, $user, $state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        $this->validateState($issue, $event, $command);
    }
}
