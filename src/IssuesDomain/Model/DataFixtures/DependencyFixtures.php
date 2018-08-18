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
use eTraxis\IssuesDomain\Model\Entity\Dependency;

/**
 * Test fixtures for 'Dependency' entity.
 */
class DependencyFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            IssueFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            'req:%s:2' => ['req:%s:3'],
            'req:%s:5' => ['req:%s:3'],
            'req:%s:6' => ['task:%s:8', 'req:%s:1'],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $issues) {

                /** @var \eTraxis\IssuesDomain\Model\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));

                foreach ($issues as $iref) {

                    /** @var \eTraxis\IssuesDomain\Model\Entity\Issue $issue2 */
                    $issue2 = $this->getReference(sprintf($iref, $pref));

                    $dependency = new Dependency($issue, $issue2);

                    $manager->persist($dependency);
                }
            }
        }

        $manager->flush();
    }
}
