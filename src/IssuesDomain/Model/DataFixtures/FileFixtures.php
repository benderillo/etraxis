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
use eTraxis\IssuesDomain\Model\Entity\File;

/**
 * Test fixtures for 'File' entity.
 */
class FileFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
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
                [
                    'Inventore.pdf',
                    175971,     // 171.85 KB
                    'application/pdf',
                ],
            ],

            'task:%s:2' => [
                [
                    'Beatae nesciunt natus suscipit iure assumenda commodi.docx',
                    217948,     // 212.84 KB
                    'application/vnd\.ms-word',
                ],
                [
                    'Nesciunt nulla sint amet.xslx',
                    6037279,    // 5895.78 KB
                    'application/vnd\.ms-excel',
                ],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $files) {

                /** @var \eTraxis\IssuesDomain\Model\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                /** @var Event[] $events */
                $events = $manager->getRepository(Event::class)->findBy([
                    'type'  => EventType::FILE_ATTACHED,
                    'issue' => $issue,
                ], [
                    'id' => 'ASC',
                ]);

                foreach ($files as $index => $row) {

                    $file = new File($events[$index], $row[0], $row[1], $row[2]);

                    $manager->persist($file);
                }
            }
        }

        $manager->flush();
    }
}
