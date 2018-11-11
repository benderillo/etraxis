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
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Event validation exception.
 */
class InvalidEventException extends \Exception
{
    protected $event;
    protected $violations;

    /**
     * InvalidEventException constructor.
     *
     * @param Event                            $event
     * @param ConstraintViolationListInterface $violations
     * @param null|\Throwable                  $previous
     */
    public function __construct(Event $event, ConstraintViolationListInterface $violations, \Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Validation failed for %s with %d violation(s).', get_class($event), $violations->count()),
            0,
            $previous
        );

        $this->event      = $event;
        $this->violations = $violations;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getViolations()
    {
        return $this->violations;
    }
}
