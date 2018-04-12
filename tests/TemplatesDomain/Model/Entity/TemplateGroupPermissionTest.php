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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class TemplateGroupPermissionTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $group = new Group($project);
        $this->setProperty($group, 'id', 3);

        $permission = new TemplateGroupPermission($template, $group, TemplatePermission::EDIT_ISSUES);
        self::assertSame($template, $this->getProperty($permission, 'template'));
        self::assertSame($group, $this->getProperty($permission, 'group'));
        self::assertSame(TemplatePermission::EDIT_ISSUES, $this->getProperty($permission, 'permission'));
    }

    public function testConstructorExceptionGroup()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: foo');

        $project1 = new Project();
        $this->setProperty($project1, 'id', 1);

        $project2 = new Project();
        $this->setProperty($project2, 'id', 2);

        $template = new Template($project1);
        $this->setProperty($template, 'id', 3);

        $group = new Group($project2);
        $this->setProperty($group, 'id', 4);
        $group->name = 'foo';

        new TemplateGroupPermission($template, $group, TemplatePermission::EDIT_ISSUES);
    }

    public function testConstructorExceptionPermission()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown permission: bar');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $group = new Group($project);
        $this->setProperty($group, 'id', 3);

        new TemplateGroupPermission($template, $group, 'bar');
    }
}
