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

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Event bus.
 */
class EventBus implements EventBusInterface
{
    protected $logger;
    protected $dispatcher;
    protected $validator;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface          $logger
     * @param EventDispatcherInterface $dispatcher
     * @param ValidatorInterface       $validator
     * @param EntityManagerInterface   $manager
     */
    public function __construct(
        LoggerInterface          $logger,
        EventDispatcherInterface $dispatcher,
        ValidatorInterface       $validator,
        EntityManagerInterface   $manager
    )
    {
        $this->logger     = $logger;
        $this->dispatcher = $dispatcher;
        $this->validator  = $validator;
        $this->manager    = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function notify(Event $event)
    {
        $violations = $this->validator->validate($event);

        if (count($violations)) {
            throw new InvalidEventException($event, $violations);
        }

        $this->manager->beginTransaction();
        $start = microtime(true);

        try {
            $this->dispatcher->dispatch(get_class($event), $event);
            $this->manager->flush();
            $this->manager->commit();
        }
        catch (\Exception $e) {
            $this->manager->rollback();
        }
        finally {
            $stop = microtime(true);
            $this->logger->debug('Event processing time', [$stop - $start, get_class($event)]);
        }
    }
}
