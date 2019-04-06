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

use eTraxis\IssuesDomain\Application\Command\ReassignIssueCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class ReassignIssueHandler
{
    protected $security;
    protected $tokens;
    protected $userRepository;
    protected $issueRepository;
    protected $eventRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $userRepository
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        UserRepository                $userRepository,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->userRepository  = $userRepository;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Command handler.
     *
     * @param ReassignIssueCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(ReassignIssueCommand $command): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\IssuesDomain\Model\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        $responsible = $this->userRepository->find($command->responsible);

        if (!$responsible) {
            throw new NotFoundHttpException('Unknown user.');
        }

        if (!$this->security->isGranted(IssueVoter::REASSIGN_ISSUE, [$issue, $responsible])) {
            throw new AccessDeniedHttpException('You are not allowed to reassign this issue.');
        }

        if ($issue->responsible !== $responsible) {

            $issue->responsible = $responsible;

            $event = new Event(EventType::ISSUE_ASSIGNED, $issue, $user, $responsible->id);

            $this->issueRepository->persist($issue);
            $this->eventRepository->persist($event);
        }
    }
}
