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

use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
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

    public function testJsonSerialize()
    {
        $expected = [
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

        self::assertSame($expected, $template->jsonSerialize());
    }

    public function testInitialState()
    {
        $template = new Template(new Project());
        self::assertNull($template->initialState);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 1);

        $intermediate = new State($template, StateType::INTERMEDIATE);
        $this->setProperty($initial, 'id', 2);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($initial, 'id', 3);

        /** @var \Doctrine\Common\Collections\ArrayCollection $states */
        $states = $this->getProperty($template, 'statesCollection');

        $states->add($intermediate);
        $states->add($final);
        self::assertNull($template->initialState);

        $states->add($initial);
        self::assertSame($initial, $template->initialState);
    }

    public function testStates()
    {
        $template = new Template(new Project());
        self::assertSame([], $template->states);

        /** @var \Doctrine\Common\Collections\ArrayCollection $states */
        $states = $this->getProperty($template, 'statesCollection');
        $states->add('State A');
        $states->add('State B');

        self::assertSame(['State A', 'State B'], $template->states);
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
