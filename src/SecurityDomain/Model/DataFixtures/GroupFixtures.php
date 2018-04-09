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

namespace eTraxis\SecurityDomain\Model\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\DataFixtures\ProjectFixtures;

/**
 * Test fixtures for 'Group' entity.
 */
class GroupFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            'managers'   => 'Managers',
            'developers' => 'Developers',
            'clients'    => 'Clients',
            'support'    => 'Support Engineers',
        ];

        $globals = [
            'staff',
            'clients',
        ];

        // Project groups.

        foreach (['a', 'b', 'c'] as $pref) {

            /** @var \eTraxis\TemplatesDomain\Model\Entity\Project $project */
            $project = $this->getReference('project:' . $pref);

            foreach ($data as $gref => $name) {

                $group = new Group($project);

                $group->name        = $name;
                $group->description = sprintf('%s %s', $name, mb_strtoupper($pref));

                $this->addReference(sprintf('%s:%s', $gref, $pref), $group);

                $manager->persist($group);
            }
        }

        // Global groups.

        foreach ($globals as $ref) {

            $group = new Group();

            $group->name = 'Company ' . ucwords($ref);

            $this->addReference($ref, $group);

            $manager->persist($group);
        }

        $manager->flush();
    }
}
