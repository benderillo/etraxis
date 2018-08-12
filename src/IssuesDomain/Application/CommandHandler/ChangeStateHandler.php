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
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\FieldValueRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\TemplatesDomain\Application\Service\FieldServiceInterface;
use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class ChangeStateHandler
{
    protected $security;
    protected $validator;
    protected $tokens;
    protected $userRepository;
    protected $stateRepository;
    protected $issueRepository;
    protected $eventRepository;
    protected $valueRepository;
    protected $fieldService;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param StateRepository               $stateRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FieldValueRepository          $valueRepository
     * @param FieldServiceInterface         $fieldService
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        StateRepository               $stateRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FieldValueRepository          $valueRepository,
        FieldServiceInterface         $fieldService
    )
    {
        $this->security        = $security;
        $this->validator       = $validator;
        $this->tokens          = $tokens;
        $this->userRepository  = $userRepository;
        $this->stateRepository = $stateRepository;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->valueRepository = $valueRepository;
        $this->fieldService    = $fieldService;
    }

    /**
     * Command handler.
     *
     * @param ChangeStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return Issue
     */
    public function handle(ChangeStateCommand $command): Issue
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

        $issue->state = $state;

        $event = new Event(EventType::STATE_CHANGED, $issue, $user, $state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        // Validate field values.
        $defaults    = [];
        $constraints = [];

        foreach ($state->fields as $field) {
            $defaults[$field->id]    = null;
            $constraints[$field->id] = $this->fieldService->getValidationConstraints($field);
        }

        $command->fields = $command->fields + $defaults;

        /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor(ChangeStateCommand::class);

        if ($state->responsible === StateResponsible::ASSIGN) {
            $metadata->addPropertyConstraint('responsible', new Assert\NotBlank());
        }

        $metadata->addPropertyConstraint('fields', new Assert\Collection([
            'fields'             => $constraints,
            'allowExtraFields'   => false,
            'allowMissingFields' => false,
        ]));

        $errors = $this->validator->validate($command);

        if (count($errors)) {
            throw InvalidCommandException::onCommand($command, $errors);
        }

        // Set field values.
        foreach ($state->fields as $field) {
            $this->valueRepository->setFieldValue($issue, $event, $field, $command->fields[$field->id]);
        }

        // Whether the issue must be assigned.
        if ($state->responsible === StateResponsible::ASSIGN) {

            $issue->responsible = $this->userRepository->find($command->responsible);

            if (!$issue->responsible) {
                throw new NotFoundHttpException('Unknown user.');
            }

            if (!$this->security->isGranted(IssueVoter::ASSIGN_ISSUE, [$state, $issue->responsible])) {
                throw new AccessDeniedHttpException('The issue cannot be assigned to specified user.');
            }

            $event2 = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $issue->responsible->id);

            $this->issueRepository->persist($issue);
            $this->eventRepository->persist($event2);
        }
        elseif ($state->responsible === StateResponsible::REMOVE) {

            $issue->responsible = null;
        }

        return $issue;
    }
}
