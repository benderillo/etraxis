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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\IssuesDomain\Application\Command\AbstractIssueCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\FieldValueRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base command handler for issues.
 */
abstract class AbstractIssueHandler
{
    protected $translator;
    protected $security;
    protected $validator;
    protected $tokens;
    protected $userRepository;
    protected $issueRepository;
    protected $eventRepository;
    protected $valueRepository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FieldValueRepository          $valueRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        TranslatorInterface           $translator,
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FieldValueRepository          $valueRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->translator      = $translator;
        $this->security        = $security;
        $this->validator       = $validator;
        $this->tokens          = $tokens;
        $this->userRepository  = $userRepository;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->valueRepository = $valueRepository;
        $this->manager         = $manager;
    }

    /**
     * Validates and processes state fields of specified command.
     *
     * @param Issue                $issue   Target issue.
     * @param Event                $event   Current event.
     * @param AbstractIssueCommand $command Current command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    protected function validateState(Issue $issue, Event $event, AbstractIssueCommand $command): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        // Validate field values.
        $defaults    = [];
        $constraints = [];

        foreach ($issue->state->fields as $field) {
            $defaults[$field->id]    = null;
            $constraints[$field->id] = $field->getFacade($this->manager)->getValidationConstraints($this->translator);
        }

        $command->fields = $command->fields + $defaults;

        /** @var \Symfony\Component\Validator\Mapping\ClassMetadata $metadata */
        $metadata = $this->validator->getMetadataFor($command);

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

        // Set field values.
        foreach ($issue->state->fields as $field) {
            $this->valueRepository->setFieldValue($issue, $event, $field, $command->fields[$field->id]);
        }

        // Whether the issue must be assigned.
        if ($issue->state->responsible === StateResponsible::ASSIGN) {

            $issue->responsible = $this->userRepository->find($command->responsible);

            if (!$issue->responsible) {
                throw new NotFoundHttpException('Unknown user.');
            }

            if (!$this->security->isGranted(IssueVoter::ASSIGN_ISSUE, [$issue->state, $issue->responsible])) {
                throw new AccessDeniedHttpException('The issue cannot be assigned to specified user.');
            }

            $event2 = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $issue->responsible->id);

            $this->issueRepository->persist($issue);
            $this->eventRepository->persist($event2);
        }
        elseif ($issue->state->responsible === StateResponsible::REMOVE) {

            $issue->responsible = null;

            $this->issueRepository->persist($issue);
        }
    }
}
