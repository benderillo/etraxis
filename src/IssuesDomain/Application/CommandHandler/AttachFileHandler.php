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
use eTraxis\IssuesDomain\Application\Command\AttachFileCommand;
use eTraxis\IssuesDomain\Application\Voter\IssueVoter;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\IssuesDomain\Model\Repository\EventRepository;
use eTraxis\IssuesDomain\Model\Repository\FileRepository;
use eTraxis\IssuesDomain\Model\Repository\IssueRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class AttachFileHandler
{
    protected const MEGABYTE = 1048576;

    protected $security;
    protected $tokens;
    protected $issueRepository;
    protected $eventRepository;
    protected $fileRepository;
    protected $manager;

    /** @var int Maximum allowed size of a single file. */
    protected $maxsize;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TokenStorageInterface         $tokens
     * @param IssueRepository               $issueRepository
     * @param EventRepository               $eventRepository
     * @param FileRepository                $fileRepository
     * @param EntityManagerInterface        $manager
     * @param int                           $maxsize
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TokenStorageInterface         $tokens,
        IssueRepository               $issueRepository,
        EventRepository               $eventRepository,
        FileRepository                $fileRepository,
        EntityManagerInterface        $manager,
        int                           $maxsize
    )
    {
        $this->security        = $security;
        $this->tokens          = $tokens;
        $this->issueRepository = $issueRepository;
        $this->eventRepository = $eventRepository;
        $this->fileRepository  = $fileRepository;
        $this->manager         = $manager;
        $this->maxsize         = $maxsize;
    }

    /**
     * Command handler.
     *
     * @param AttachFileCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     *
     * @return File
     */
    public function handle(AttachFileCommand $command): File
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        /** @var null|\eTraxis\IssuesDomain\Model\Entity\Issue $issue */
        $issue = $this->issueRepository->find($command->issue);

        if (!$issue) {
            throw new NotFoundHttpException('Unknown issue.');
        }

        if (!$this->security->isGranted(IssueVoter::ATTACH_FILE, $issue)) {
            throw new AccessDeniedHttpException('You are not allowed to attach a file to this issue.');
        }

        if ($command->file->getSize() > $this->maxsize * self::MEGABYTE) {
            throw new BadRequestHttpException(sprintf('The file size must not exceed %d MB.', $this->maxsize));
        }

        $event = new Event(EventType::FILE_ATTACHED, $issue, $user);

        $file = new File($event,
            $command->file->getClientOriginalName(),
            $command->file->getSize(),
            $command->file->getMimeType());

        $this->eventRepository->persist($event);
        $this->fileRepository->persist($file);

        $this->manager->flush();

        $query = $this->manager->createQueryBuilder()
            ->update(Event::class, 'event')
            ->set('event.parameter', $file->id)
            ->where('event.id = :event')
            ->setParameter('event', $event->id);

        $query->getQuery()->execute();

        $this->manager->refresh($event);

        $directory = dirname($this->fileRepository->getFullPath($file));
        $command->file->move($directory, $file->uuid);

        return $file;
    }
}
