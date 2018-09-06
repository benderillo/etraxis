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
use eTraxis\IssuesDomain\Application\Command\CloneIssueCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\FieldValueRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CloneIssueHandler extends AbstractIssueHandler
{
    protected $templateRepository;

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
     * @param TemplateRepository            $templateRepository
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
        EntityManagerInterface        $manager,
        TemplateRepository            $templateRepository
    )
    {
        parent::__construct($translator, $security, $validator, $tokens, $userRepository, $issueRepository, $eventRepository, $valueRepository, $manager);

        $this->templateRepository = $templateRepository;
    }

    /**
     * Command handler.
     *
     * @param CloneIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return Issue
     */
    public function handle(CloneIssueCommand $command): Issue
    {
        /** @var null|Issue $origin */
        $origin = $this->issueRepository->find($command->issue);

        if (!$origin) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::CREATE_ISSUE, $origin->template)) {
            throw new AccessDeniedHttpException('You are not allowed to create new issue.');
        }

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $author */
        $author = $this->tokens->getToken()->getUser();

        $issue = new Issue($author, $origin);

        $issue->state   = $origin->template->initialState;
        $issue->subject = $command->subject;

        $event = new Event(EventType::ISSUE_CREATED, $issue, $author, $issue->state->id);

        $this->issueRepository->persist($issue);
        $this->eventRepository->persist($event);

        $this->validateState($issue, $event, $command);

        return $issue;
    }
}
