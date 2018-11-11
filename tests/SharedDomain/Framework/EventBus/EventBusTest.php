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

use eTraxis\Tests\WebTestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

class EventBusTest extends WebTestCase
{
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

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $this->client->getContainer()->get('event_dispatcher');

        /** @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
        $validator = $this->client->getContainer()->get('validator');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $eventbus = new EventBus($logger, $dispatcher, $validator, $manager);

        $event = $this->createEvent([
            'property' => 1,
        ]);

        $eventbus->notify($event);

        self::assertTrue($logger->contains('Event processing time'));
    }

    public function testViolations()
    {
        $this->expectException(InvalidEventException::class);

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $this->client->getContainer()->get('event_dispatcher');

        /** @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
        $validator = $this->client->getContainer()->get('validator');

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $eventbus = new EventBus(new NullLogger(), $dispatcher, $validator, $manager);

        $event = $this->createEvent([
            'property' => 0,
        ]);

        $eventbus->notify($event);
    }

    protected function createEvent(array $data = [])
    {
        return new class($data) extends Event {
            use DataTransferObjectTrait;

            /**
             * @Assert\Range(min="1", max="100")
             */
            public $property;
        };
    }
}
