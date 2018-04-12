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

namespace eTraxis\TemplatesDomain\Model\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\TemplatesDomain\Model\Entity\Template;

/**
 * Test fixtures for 'Template' entity.
 */
class TemplateFixtures extends Fixture implements DependentFixtureInterface
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
            'a' => true,
            'b' => true,
            'c' => false,
        ];

        foreach ($data as $ref => $isLocked) {

            /** @var \eTraxis\TemplatesDomain\Model\Entity\Project $project */
            $project = $this->getReference('project:' . $ref);

            $development = new Template($project);
            $support     = new Template($project);

            $development->name        = 'Development';
            $development->prefix      = 'task';
            $development->description = 'Development ' . mb_strtoupper($ref);
            $development->isLocked    = $isLocked;

            $support->name        = 'Support';
            $support->prefix      = 'issue';
            $support->description = 'Support ' . mb_strtoupper($ref);
            $support->criticalAge = 3;
            $support->frozenTime  = 7;
            $support->isLocked    = $ref === 'a';

            $this->addReference('task:' . $ref, $development);
            $this->addReference('issue:' . $ref, $support);

            $manager->persist($development);
            $manager->persist($support);
        }

        $manager->flush();
    }
}
