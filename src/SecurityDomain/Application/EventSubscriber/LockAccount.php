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

namespace eTraxis\SecurityDomain\Application\EventSubscriber;

use eTraxis\SecurityDomain\Application\Event\LoginFailedEvent;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class LockAccount implements EventSubscriberInterface
{
    protected $logger;
    protected $repository;
    protected $authFailures;
    protected $lockDuration;

    /**
     * Dependency Injection constructor.
     *
     * @param LoggerInterface $logger
     * @param UserRepository  $repository
     * @param null|int        $authFailures
     * @param null|int        $lockDuration
     */
    public function __construct(
        LoggerInterface $logger,
        UserRepository  $repository,
        ?int            $authFailures,
        ?int            $lockDuration
    )
    {
        $this->logger       = $logger;
        $this->repository   = $repository;
        $this->authFailures = $authFailures;
        $this->lockDuration = $lockDuration;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LoginFailedEvent::class => 'handle',
        ];
    }

    /**
     * Increases locks count for specified account.
     *
     * @param LoginFailedEvent $event
     *
     * @throws \Exception
     */
    public function handle(LoginFailedEvent $event): void
    {
        if ($this->authFailures === null) {
            return;
        }

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        if ($user = $this->repository->findOneByUsername($event->username)) {

            $this->logger->info('Authentication failure', [$event->username]);

            if ($user->incAuthFailures() >= $this->authFailures) {

                if ($this->lockDuration === null) {
                    $user->lockAccount();
                }
                else {
                    $interval = sprintf('PT%dM', $this->lockDuration);
                    $user->lockAccount(date_create()->add(new \DateInterval($interval)));
                }
            }

            $this->repository->persist($user);
        }
    }
}
