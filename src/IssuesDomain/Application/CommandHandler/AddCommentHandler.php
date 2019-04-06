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

use eTraxis\IssuesDomain\Application\Command\AddCommentCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Comment;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Repository\CommentRepository;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class AddCommentHandler
{
    protected $security;
    protected $tokens;
    protected $issueRepository;
    protected $eventRepository;
    protected $commentRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param CommentRepository             $commentRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        CommentRepository             $commentRepository
    )
    {
        $this->security          = $security;
        $this->tokens            = $tokens;
        $this->issueRepository   = $issueRepository;
        $this->eventRepository   = $eventRepository;
        $this->commentRepository = $commentRepository;
    }

    /**
     * Command handler.
     *
     * @param AddCommentCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(AddCommentCommand $command): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\IssuesDomain\Model\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if ($command->private) {
            if (!$this->security->isGranted(IssueVoter::ADD_PRIVATE_COMMENT, $issue)) {
                throw new AccessDeniedHttpException('You are not allowed to comment this issue privately.');
            }
        }
        else {
            if (!$this->security->isGranted(IssueVoter::ADD_PUBLIC_COMMENT, $issue)) {
                throw new AccessDeniedHttpException('You are not allowed to comment this issue.');
            }
        }

        $event = new Event(
            $command->private ? EventType::PRIVATE_COMMENT : EventType::PUBLIC_COMMENT,
            $issue,
            $user
        );

        $comment = new Comment($event);

        $comment->body      = $command->body;
        $comment->isPrivate = $command->private;

        $this->eventRepository->persist($event);
        $this->commentRepository->persist($comment);
    }
}
