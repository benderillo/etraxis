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

use Doctrine\ORM\EntityManager;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\FieldTypes;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
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

    public function testGetFacade()
    {
        $expected = [
            FieldType::CHECKBOX => FieldTypes\CheckboxInterface::class,
            FieldType::DATE     => FieldTypes\DateInterface::class,
            FieldType::DECIMAL  => FieldTypes\DecimalInterface::class,
            FieldType::DURATION => FieldTypes\DurationInterface::class,
            FieldType::ISSUE    => FieldTypes\IssueInterface::class,
            FieldType::LIST     => FieldTypes\ListInterface::class,
            FieldType::NUMBER   => FieldTypes\NumberInterface::class,
            FieldType::STRING   => FieldTypes\StringInterface::class,
            FieldType::TEXT     => FieldTypes\TextInterface::class,
        ];

        $manager = $this->createMock(EntityManager::class);
        $manager
            ->method('getRepository')
            ->willReturnMap([
                [DecimalValue::class, $this->createMock(DecimalValueRepository::class)],
                [ListItem::class, $this->createMock(ListItemRepository::class)],
                [StringValue::class, $this->createMock(StringValueRepository::class)],
                [TextValue::class, $this->createMock(TextValueRepository::class)],
            ]);

        /** @var EntityManager $manager */
        foreach ($expected as $type => $class) {
            $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), $type);
            self::assertInstanceOf($class, $field->getFacade($manager));
        }

        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        $this->setProperty($field, 'type', 'unknown');
        self::assertNull($field->getFacade($manager));
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
