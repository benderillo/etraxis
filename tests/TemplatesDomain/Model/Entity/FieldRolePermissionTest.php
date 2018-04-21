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

use eTraxis\TemplatesDomain\Model\Dictionary\FieldPermission;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class FieldRolePermissionTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $permission = new FieldRolePermission($field, SystemRole::AUTHOR, FieldPermission::READ_WRITE);
        self::assertSame($field, $this->getProperty($permission, 'field'));
        self::assertSame(SystemRole::AUTHOR, $this->getProperty($permission, 'role'));
        self::assertSame(FieldPermission::READ_WRITE, $this->getProperty($permission, 'permission'));
    }

    public function testConstructorExceptionRole()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown system role: foo');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        new FieldRolePermission($field, 'foo', FieldPermission::READ_WRITE);
    }

    public function testConstructorExceptionPermission()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown permission: bar');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 3);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        new FieldRolePermission($field, SystemRole::AUTHOR, 'bar');
    }
}
