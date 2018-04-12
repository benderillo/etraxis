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
use eTraxis\SecurityDomain\Model\DataFixtures\GroupFixtures;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Entity\StateGroupTransition;
use eTraxis\TemplatesDomain\Model\Entity\StateRoleTransition;

/**
 * Test fixtures for 'State' entity.
 */
class StateTransitionFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            GroupFixtures::class,
            StateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task' => [

                SystemRole::RESPONSIBLE => [
                    'assigned:%s' => 'completed:%s',
                ],

                'managers:%s' => [
                    'new:%s'      => 'assigned:%s',
                    'assigned:%s' => 'duplicated:%s',
                ],
            ],

            'issue' => [

                SystemRole::AUTHOR => [
                    'opened:%s'   => 'resolved:%s',
                    'resolved:%s' => 'opened:%s',
                ],

                SystemRole::RESPONSIBLE => [
                    'opened:%s' => 'resolved:%s',
                ],

                'managers:%s' => [
                    'submitted:%s' => 'opened:%s',
                ],

                'support:%s' => [
                    'submitted:%s' => 'opened:%s',
                ],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $tref => $groups) {

                foreach ($groups as $gref => $transitions) {

                    foreach ($transitions as $from => $to) {

                        /** @var \eTraxis\TemplatesDomain\Model\Entity\State $fromState */
                        $fromState = $this->getReference(sprintf($from, $pref));

                        /** @var \eTraxis\TemplatesDomain\Model\Entity\State $toState */
                        $toState = $this->getReference(sprintf($to, $pref));

                        if (SystemRole::has($gref)) {
                            $roleTransition = new StateRoleTransition($fromState, $toState, $gref);
                            $manager->persist($roleTransition);
                        }
                        else {
                            /** @var \eTraxis\SecurityDomain\Model\Entity\Group $group */
                            $group = $this->getReference(sprintf($gref, $pref));

                            $groupTransition = new StateGroupTransition($fromState, $toState, $group);
                            $manager->persist($groupTransition);
                        }
                    }
                }
            }
        }

        $manager->flush();
    }
}
