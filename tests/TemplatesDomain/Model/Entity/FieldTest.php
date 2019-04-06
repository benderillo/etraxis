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

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Entity\Field
 */
class FieldTest extends TestCase
{
    use ReflectionTrait;

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        $field = new Field($state, FieldType::LIST);
        self::assertSame($state, $this->getProperty($field, 'state'));
        self::assertSame(FieldType::LIST, $this->getProperty($field, 'type'));
    }

    /**
     * @covers ::__construct
     */
    public function testConstructorException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown field type: foo');

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);
        $this->setProperty($state, 'id', 1);

        new Field($state, 'foo');
    }

    /**
     * @covers ::getFacade
     */
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

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonSerialize()
    {
        $expected = [
            'id'          => 4,
            'state'       => [
                'id'          => 3,
                'template'    => [
                    'id'          => 2,
                    'project'     => [
                        'id'          => 1,
                        'name'        => 'Project',
                        'description' => 'Test project',
                        'created'     => time(),
                        'suspended'   => false,
                    ],
                    'name'        => 'Bugfix',
                    'prefix'      => 'bug',
                    'description' => 'Found bugs',
                    'critical'    => 5,
                    'frozen'      => null,
                    'locked'      => true,
                ],
                'name'        => 'New',
                'type'        => 'initial',
                'responsible' => 'remove',
                'next'        => null,
            ],
            'name'        => 'Customer reported',
            'type'        => 'checkbox',
            'description' => null,
            'position'    => 1,
            'required'    => false,
        ];

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $project->name        = 'Project';
        $project->description = 'Test project';

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $template->name        = 'Bugfix';
        $template->prefix      = 'bug';
        $template->description = 'Found bugs';
        $template->criticalAge = 5;

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $state->name = 'New';

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 4);

        $field->name       = 'Customer reported';
        $field->position   = 1;
        $field->isRequired = false;

        self::assertSame($expected, $field->jsonSerialize());
    }

    /**
     * @covers ::getters
     * @covers ::remove
     */
    public function testIsRemoved()
    {
        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        self::assertFalse($field->isRemoved);

        $field->remove();
        self::assertTrue($field->isRemoved);
    }

    /**
     * @covers ::getters
     */
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

    /**
     * @covers ::getters
     */
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
