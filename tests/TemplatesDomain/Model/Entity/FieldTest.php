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

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        self::assertSame($state, $this->getProperty($field, 'state'));
        self::assertSame(FieldType::LIST, $this->getProperty($field, 'type'));
    }

    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field type: foo');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        new Field($state, 'foo');
    }

    public function testIsRemoved()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertFalse($field->isRemoved);

        $field->remove();
        self::assertTrue($field->isRemoved);
    }

    public function testRolePermissions()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertSame([], $field->rolePermissions);

        /** @var \Doctrine\Common\Collections\ArrayCollection $permissions */
        $permissions = $this->getProperty($field, 'rolePermissionsCollection');
        $permissions->add('Role permission A');
        $permissions->add('Role permission B');

        self::assertSame(['Role permission A', 'Role permission B'], $field->rolePermissions);
    }

    public function testGroupPermissions()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertSame([], $field->groupPermissions);

        /** @var \Doctrine\Common\Collections\ArrayCollection $permissions */
        $permissions = $this->getProperty($field, 'groupPermissionsCollection');
        $permissions->add('Group permission A');
        $permissions->add('Group permission B');

        self::assertSame(['Group permission A', 'Group permission B'], $field->groupPermissions);
    }
}
