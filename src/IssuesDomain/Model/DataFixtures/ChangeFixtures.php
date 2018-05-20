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
use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\TemplatesDomain\Model\DataFixtures\FieldFixtures;
use eTraxis\TemplatesDomain\Model\DataFixtures\ListItemFixtures;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;

/**
 * Test fixtures for 'Change' entity.
 */
class ChangeFixtures extends Fixture implements DependentFixtureInterface
{
    protected const EVENT_TYPE      = 0;
    protected const EVENT_TIMESTAMP = 1;
    protected const CHANGED_FIELDS  = 2;

    protected const SECS_IN_DAY = 86400;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            FieldFixtures::class,
            ListItemFixtures::class,
            IssueFixtures::class,
            EventFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task:%s:1' => [

                // Modified.
                [EventType::ISSUE_EDITED, 0, [
                    'subject'         => ['Task 1', 'Development task 1'],
                    'new:%s:priority' => [3, 2],
                ]],
            ],

            'task:%s:2' => [

                // Reopened in 'New' state.
                [EventType::ISSUE_REOPENED, 2, [
                    'new:%s:priority'    => [3, 1],
                    'new:%s:description' => [
                        'Velit voluptatem rerum nulla quos.',
                        'Velit voluptatem rerum nulla quos soluta excepturi omnis.',
                    ],
                ]],

                // Moved to 'Assigned' state second time.
                [EventType::STATE_CHANGED, 2, [
                    'assigned:%s:due date' => [14, 7],
                ]],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $events) {

                /** @var \eTraxis\IssuesDomain\Model\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                foreach ($events as $row) {

                    $timestamp = $issue->createdAt + $row[self::EVENT_TIMESTAMP] * self::SECS_IN_DAY;

                    /** @var Event $event */
                    $event = $manager->getRepository(Event::class)->findOneBy([
                        'type'      => $row[self::EVENT_TYPE],
                        'issue'     => $issue,
                        'createdAt' => $timestamp,
                    ]);

                    foreach ($row[self::CHANGED_FIELDS] as $fref => $values) {

                        $field    = null;
                        $oldValue = null;
                        $newValue = null;

                        if ($fref === 'subject') {

                            /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
                            $repository = $manager->getRepository(StringValue::class);

                            $oldValue = $repository->get($values[0])->id;
                            $newValue = $repository->get($values[1])->id;
                        }
                        else {

                            /** @var \eTraxis\TemplatesDomain\Model\Entity\Field $field */
                            $field = $this->getReference(sprintf($fref, $pref));

                            switch ($field->type) {

                                case FieldType::TEXT:

                                    /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository $repository */
                                    $repository = $manager->getRepository(TextValue::class);

                                    $oldValue = $repository->get($values[0])->id;
                                    $newValue = $repository->get($values[1])->id;

                                    break;

                                case FieldType::LIST:

                                    /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository $repository */
                                    $repository = $manager->getRepository(ListItem::class);

                                    $oldValue = $repository->findOneByValue($field, $values[0])->id;
                                    $newValue = $repository->findOneByValue($field, $values[1])->id;

                                    break;

                                case FieldType::DATE:

                                    $oldValue = $issue->createdAt + $values[0] * self::SECS_IN_DAY;
                                    $newValue = $issue->createdAt + $values[1] * self::SECS_IN_DAY;

                                    break;
                            }
                        }

                        $change = new Change($event, $field, $oldValue, $newValue);

                        $manager->persist($change);
                    }
                }
            }
        }

        $manager->flush();
    }
}
