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

use eTraxis\SecurityDomain\Application\Event\LoginSuccessfulEvent;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class UnlockAccount implements EventSubscriberInterface
{
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LoginSuccessfulEvent::class => 'handle',
        ];
    }

    /**
     * Clears locks count for specified account.
     *
     * @param LoginSuccessfulEvent $event
     */
    public function handle(LoginSuccessfulEvent $event): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        if ($user = $this->repository->findOneByUsername($event->username)) {

            $user->unlockAccount();

            $this->repository->persist($user);
        }
    }
}
