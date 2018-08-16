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

namespace eTraxis\IssuesDomain\Model\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\SecurityDomain\Model\DataFixtures\UserFixtures;
use eTraxis\TemplatesDomain\Model\DataFixtures\StateFixtures;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\Tests\ReflectionTrait;

/**
 * Test fixtures for 'Event' entity.
 */
class EventFixtures extends Fixture implements DependentFixtureInterface
{
    use ReflectionTrait;
    use UsersTrait;

    protected const EVENT_TYPE      = 0;
    protected const EVENT_USER      = 1;
    protected const EVENT_TIMESTAMP = 2;
    protected const EVENT_PARAMETER = 3;

    protected const SECS_IN_DAY = 86400;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            StateFixtures::class,
            IssueFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task:%s:1' => [
                [EventType::ISSUE_CREATED,  $this->manager1,   0, 'new'],
                [EventType::ISSUE_EDITED,   $this->manager1,   0, null],
                [EventType::STATE_CHANGED,  $this->manager1,   0, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager1,   0, $this->developer1],
                [EventType::ISSUE_CLOSED,   $this->developer1, 3, 'completed'],
            ],

            'task:%s:2' => [
                [EventType::ISSUE_CREATED,  $this->manager2,   0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager1,   0, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager1,   0, $this->developer3],
                [EventType::ISSUE_CLOSED,   $this->developer3, 2, 'completed'],
                [EventType::ISSUE_REOPENED, $this->manager2,   2, 'new'],
                [EventType::STATE_CHANGED,  $this->manager2,   2, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager2,   2, $this->developer3],
            ],

            'task:%s:3' => [
                [EventType::ISSUE_CREATED,  $this->manager3,   0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager3,   0, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager3,   0, $this->developer1],
                [EventType::ISSUE_CLOSED,   $this->developer1, 5, 'completed'],
            ],

            'task:%s:4' => [
                [EventType::ISSUE_CREATED,  $this->developer1, 0, 'new'],
                [EventType::ISSUE_CLOSED,   $this->manager2,   0, 'duplicated'],
            ],

            'task:%s:5' => [
                [EventType::ISSUE_CREATED,  $this->manager3,   0, 'new'],
            ],

            'task:%s:6' => [
                [EventType::ISSUE_CREATED,  $this->manager3,   0, 'new'],
            ],

            'task:%s:7' => [
                [EventType::ISSUE_CREATED,  $this->developer2, 0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager2,   1, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager2,   1, $this->developer2],
                [EventType::ISSUE_CLOSED,   $this->manager3,   2, 'duplicated'],
            ],

            'task:%s:8' => [
                [EventType::ISSUE_CREATED,  $this->developer2, 0, 'new'],
                [EventType::STATE_CHANGED,  $this->manager1,   3, 'assigned'],
                [EventType::ISSUE_ASSIGNED, $this->manager1,   3, $this->developer2],
            ],

            'req:%s:1' => [
                [EventType::ISSUE_CREATED,  $this->client1,    0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->manager1,   0, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->manager1,   0, $this->support1],
                [EventType::ISSUE_CLOSED,   $this->support1,   2, 'resolved'],
            ],

            'req:%s:2' => [
                [EventType::ISSUE_CREATED,  $this->client2,    0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->support2,   0, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->support2,   0, $this->support2],
            ],

            'req:%s:3' => [
                [EventType::ISSUE_CREATED,  $this->client2,    0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->support2,   0, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->support2,   0, $this->support2],
                [EventType::ISSUE_CLOSED,   $this->support2,   2, 'resolved'],
            ],

            'req:%s:4' => [
                [EventType::ISSUE_CREATED,  $this->client3,    0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->manager2,   1, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->manager2,   1, $this->support1],
            ],

            'req:%s:5' => [
                [EventType::ISSUE_CREATED,  $this->client2,    0, 'submitted'],
                [EventType::STATE_CHANGED,  $this->support3,   0, 'opened'],
                [EventType::ISSUE_ASSIGNED, $this->support3,   0, $this->support3],
            ],

            'req:%s:6' => [
                [EventType::ISSUE_CREATED,  $this->client1,    0, 'submitted'],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $events) {

                /** @var \eTraxis\IssuesDomain\Model\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                foreach ($events as $row) {

                    /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
                    $user = $this->getReference($row[self::EVENT_USER][$pref]);

                    $timestamp = $issue->createdAt + $row[self::EVENT_TIMESTAMP] * self::SECS_IN_DAY;

                    $event = new Event($row[self::EVENT_TYPE], $issue, $user);

                    $this->setProperty($event, 'createdAt', $timestamp);
                    $this->setProperty($issue, 'changedAt', $timestamp);

                    switch ($row[self::EVENT_TYPE]) {

                        case EventType::ISSUE_CREATED:
                        case EventType::ISSUE_REOPENED:
                        case EventType::ISSUE_CLOSED:
                        case EventType::STATE_CHANGED:

                            /** @var \eTraxis\TemplatesDomain\Model\Entity\State $entity */
                            $entity = $this->getReference(sprintf('%s:%s', $row[self::EVENT_PARAMETER], $pref));
                            $this->setProperty($event, 'parameter', $entity->id);

                            $issue->state = $entity;

                            if ($entity->type === StateType::FINAL) {
                                $this->setProperty($issue, 'closedAt', $timestamp);
                            }

                            break;

                        case EventType::ISSUE_ASSIGNED:

                            /** @var \eTraxis\SecurityDomain\Model\Entity\User $entity */
                            $entity = $this->getReference($row[self::EVENT_PARAMETER][$pref]);
                            $this->setProperty($event, 'parameter', $entity->id);

                            break;
                    }

                    $manager->persist($event);
                }

                $manager->persist($issue);
            }
        }

        $manager->flush();
    }
}
