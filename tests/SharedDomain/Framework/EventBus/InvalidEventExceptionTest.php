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

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @coversDefaultClass \eTraxis\SharedDomain\Framework\EventBus\InvalidEventException
 */
class InvalidEventExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getEvent
     * @covers ::getViolations
     */
    public function testException()
    {
        $violation = $this->createMock(ConstraintViolation::class);

        $event      = new Event();
        $violations = new ConstraintViolationList([$violation]);

        $exception = new InvalidEventException($event, $violations);

        self::assertSame('Validation failed for Symfony\\Component\\EventDispatcher\\Event with 1 violation(s).', $exception->getMessage());
        self::assertSame($event, $exception->getEvent());
        self::assertSame($violations, $exception->getViolations());
    }
}
