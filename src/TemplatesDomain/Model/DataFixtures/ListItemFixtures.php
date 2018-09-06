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
use eTraxis\TemplatesDomain\Model\Entity\ListItem;

/**
 * Test fixtures for 'ListItem' entity.
 */
class ListItemFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            FieldFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [
            1 => 'high',
            2 => 'normal',
            3 => 'low',
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $value => $text) {

                /** @var \eTraxis\TemplatesDomain\Model\Entity\Field $field */
                $field = $this->getReference(sprintf('new:%s:priority', $pref));

                $item = new ListItem($field);

                $item->value = $value;
                $item->text  = $text;

                /** @var \Doctrine\ORM\EntityManagerInterface $manager */
                /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\ListInterface $facade */
                $facade = $field->getFacade($manager);
                $facade->setDefaultValue($item);

                $manager->persist($item);
            }
        }

        $manager->flush();
    }
}
