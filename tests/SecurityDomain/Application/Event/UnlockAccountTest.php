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

namespace eTraxis\SecurityDomain\Application\Event;

use eTraxis\SecurityDomain\Application\EventSubscriber\UnlockAccount;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;

/**
 * @coversDefaultClass \eTraxis\SecurityDomain\Application\EventSubscriber\UnlockAccount
 */
class UnlockAccountTest extends TransactionalTestCase
{
    /**
     * @covers ::getSubscribedEvents
     */
    public function testSubscribedEvents()
    {
        $events = UnlockAccount::getSubscribedEvents();
        self::assertArrayHasKey(LoginSuccessfulEvent::class, $events);
    }

    /**
     * @covers ::handle
     */
    public function testUnlockUser()
    {
        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('artem@example.com');
        $user->lockAccount();

        self::assertFalse($user->isAccountNonLocked());

        $event = new LoginSuccessfulEvent([
            'username' => $user->getUsername(),
        ]);

        $this->eventBus->notify($event);

        self::assertTrue($user->isAccountNonLocked());
    }
}
