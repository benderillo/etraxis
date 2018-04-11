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

namespace eTraxis\TemplatesDomain\Model\Entity;

use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        self::assertSame($project, $this->getProperty($template, 'project'));
    }

    public function testRolePermissions()
    {
        $template = new Template(new Project());
        self::assertSame([], $template->rolePermissions);

        /** @var \Doctrine\Common\Collections\ArrayCollection $permissions */
        $permissions = $this->getProperty($template, 'rolePermissionsCollection');
        $permissions->add('Role permission A');
        $permissions->add('Role permission B');

        self::assertSame(['Role permission A', 'Role permission B'], $template->rolePermissions);
    }

    public function testGroupPermissions()
    {
        $template = new Template(new Project());
        self::assertSame([], $template->groupPermissions);

        /** @var \Doctrine\Common\Collections\ArrayCollection $permissions */
        $permissions = $this->getProperty($template, 'groupPermissionsCollection');
        $permissions->add('Group permission A');
        $permissions->add('Group permission B');

        self::assertSame(['Group permission A', 'Group permission B'], $template->groupPermissions);
    }
}
