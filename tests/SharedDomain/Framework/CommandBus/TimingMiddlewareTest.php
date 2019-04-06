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

namespace eTraxis\SharedDomain\Framework\CommandBus;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * @coversDefaultClass \eTraxis\SharedDomain\Framework\CommandBus\TimingMiddleware
 */
class TimingMiddlewareTest extends TestCase
{
    /**
     * @covers ::execute
     */
    public function testTiming()
    {
        $logger = new class() extends AbstractLogger {
            protected $logs;

            public function log($level, $message, array $context = [])
            {
                $this->logs .= $message;
            }

            public function contains($message)
            {
                return mb_strpos($this->logs, $message) !== false;
            }
        };

        $command = new \stdClass();

        $middleware = new TimingMiddleware($logger);
        $middleware->execute($command, function () {
        });

        self::assertTrue($logger->contains('Command processing time'));
    }
}
