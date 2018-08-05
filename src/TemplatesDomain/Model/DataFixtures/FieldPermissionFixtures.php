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
use eTraxis\TemplatesDomain\Model\Dictionary\FieldPermission;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Entity\FieldGroupPermission;
use eTraxis\TemplatesDomain\Model\Entity\FieldRolePermission;

/**
 * Test fixtures for 'Field' entity.
 */
class FieldPermissionFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            GroupFixtures::class,
            FieldFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'new:%s:priority' => [
                SystemRole::AUTHOR => FieldPermission::READ_ONLY,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'new:%s:description' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'new:%s:error' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'new:%s:new feature' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'assigned:%s:due date' => [
                SystemRole::RESPONSIBLE => FieldPermission::READ_ONLY,
                'managers:%s'           => FieldPermission::READ_WRITE,
            ],

            'completed:%s:commit id' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'completed:%s:delta' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'completed:%s:effort' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'completed:%s:test coverage' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_WRITE,
            ],

            'duplicated:%s:task id' => [
                'managers:%s'   => FieldPermission::READ_WRITE,
                'developers:%s' => FieldPermission::READ_ONLY,
            ],

            'duplicated:%s:issue id' => [
                SystemRole::AUTHOR => FieldPermission::READ_ONLY,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'developers:%s'    => FieldPermission::READ_ONLY,
            ],

            'submitted:%s:details' => [
                SystemRole::AUTHOR => FieldPermission::READ_WRITE,
                'managers:%s'      => FieldPermission::READ_WRITE,
                'support:%s'       => FieldPermission::READ_ONLY,
                'staff'            => FieldPermission::READ_ONLY,
            ],
        ];

        foreach (['a', 'b', 'c', 'd'] as $pref) {

            foreach ($data as $fref => $groups) {

                /** @var \eTraxis\TemplatesDomain\Model\Entity\Field $field */
                $field = $this->getReference(sprintf($fref, $pref));

                foreach ($groups as $gref => $permission) {

                    if (SystemRole::has($gref)) {
                        $rolePermission = new FieldRolePermission($field, $gref, $permission);
                        $manager->persist($rolePermission);
                    }
                    else {
                        /** @var \eTraxis\SecurityDomain\Model\Entity\Group $group */
                        $group = $this->getReference(sprintf($gref, $pref));

                        $groupPermission = new FieldGroupPermission($field, $group, $permission);
                        $manager->persist($groupPermission);
                    }
                }
            }
        }

        $manager->flush();
    }
}
