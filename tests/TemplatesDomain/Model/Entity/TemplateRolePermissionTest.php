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

use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class TemplateRolePermissionTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $permission = new TemplateRolePermission($template, SystemRole::AUTHOR, TemplatePermission::EDIT_ISSUES);
        self::assertSame($template, $this->getProperty($permission, 'template'));
        self::assertSame(SystemRole::AUTHOR, $this->getProperty($permission, 'role'));
        self::assertSame(TemplatePermission::EDIT_ISSUES, $this->getProperty($permission, 'permission'));
    }

    public function testConstructorFailed()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $permission = new TemplateRolePermission($template, 'unknown', TemplatePermission::EDIT_ISSUES);
        self::assertSame($template, $this->getProperty($permission, 'template'));
        self::assertNull($this->getProperty($permission, 'role'));
        self::assertSame(TemplatePermission::EDIT_ISSUES, $this->getProperty($permission, 'permission'));
    }
}
