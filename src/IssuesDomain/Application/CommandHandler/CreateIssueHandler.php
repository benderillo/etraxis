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

use eTraxis\IssuesDomain\Application\Command\CreateIssueCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\TemplatesDomain\Application\Service\FieldService;
use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
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
class CreateIssueHandler
{
    protected $security;
    protected $validator;
    protected $tokens;
    protected $userRepository;
    protected $templateRepository;
    protected $issueRepository;
    protected $eventRepository;
    protected $fieldService;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param TemplateRepository            $templateRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FieldService                  $fieldService
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        TemplateRepository            $templateRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FieldService                  $fieldService
    )
    {
        $this->security           = $security;
        $this->validator          = $validator;
        $this->tokens             = $tokens;
        $this->userRepository     = $userRepository;
        $this->templateRepository = $templateRepository;
        $this->issueRepository    = $issueRepository;
        $this->eventRepository    = $eventRepository;
        $this->fieldService       = $fieldService;
    }

    /**
     * Command handler.
     *
     * @param CreateIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return Issue
     */
    public function handle(CreateIssueCommand $command): Issue
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Template $template */
        $template = $this->templateRepository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException('Unknown template.');
        }

        if (!$this->security->isGranted(IssueVoter::CREATE_ISSUE, $template)) {
            throw new AccessDeniedHttpException('You are not allowed to create new issue.');
        }

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $author */
        $author = $this->tokens->getToken()->getUser();

        $issue = new Issue($author);

        $issue->state   = $template->initialState;
        $issue->subject = $command->subject;

        $event = new Event(EventType::ISSUE_CREATED, $issue, $author, $issue->state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        // Validate field values.
        $defaults    = [];
        $constraints = [];

        foreach ($issue->state->fields as $field) {
            $defaults[$field->id]    = null;
            $constraints[$field->id] = $this->fieldService->getValidationConstraints($field);
        }

        $command->fields = $command->fields + $defaults;

        /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor(CreateIssueCommand::class);

        if ($issue->state->responsible === StateResponsible::ASSIGN) {
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

        // Create field values.
        foreach ($issue->state->fields as $field) {
            $this->issueRepository->setFieldValue($issue, $event, $field, $command->fields[$field->id]);
        }

        // Whether the issue must be assigned on creation.
        if ($issue->state->responsible === StateResponsible::ASSIGN) {

            $issue->responsible = $this->userRepository->find($command->responsible);

            if (!$issue->responsible) {
                throw new NotFoundHttpException('Unknown user.');
            }

            if (!$this->security->isGranted(IssueVoter::ASSIGN_ISSUE, [$issue->state, $issue->responsible])) {
                throw new AccessDeniedHttpException('New issue cannot be assigned to specified user.');
            }

            $event2 = new Event(EventType::ISSUE_ASSIGNED, $issue, $author, $issue->responsible->id);

            $this->eventRepository->persist($event2);
        }

        return $issue;
    }
}
