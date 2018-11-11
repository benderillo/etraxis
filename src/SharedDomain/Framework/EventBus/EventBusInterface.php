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

namespace eTraxis\SharedDomain\Framework\EventBus;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event bus.
 */
interface EventBusInterface
{
    /**
     * Notifies existing listeners and subscribers about specified event.
     *
     * @param Event $event
     */
    public function notify(Event $event);
}
