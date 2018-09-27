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

namespace eTraxis\IssuesDomain\Model\Entity;

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class ChangeTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 2);

        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $this->setProperty($event, 'id', 3);

        $field = new Field(new State(new Template(new Project()), StateType::INTERMEDIATE), FieldType::LIST);
        $this->setProperty($field, 'id', 4);

        $change = new Change($event, $field, null, 100);

        self::assertSame($event, $change->event);
        self::assertSame($field, $change->field);
        self::assertNull($change->oldValue);
        self::assertSame(100, $change->newValue);
    }

    public function testJsonSerialize()
    {
        $expected = [
            'id'        => 8,
            'user'      => [
                'id'       => 1,
                'email'    => 'anna@example.com',
                'fullname' => 'Anna Rodygina',
            ],
            'timestamp' => time(),
            'field'     => [
                'id'          => 5,
                'name'        => 'Customer reported',
                'type'        => 'checkbox',
                'description' => null,
                'position'    => 1,
                'required'    => false,
            ],
            'old_value' => 123,
            'new_value' => 456,
        ];

        $user = new User();
        $this->setProperty($user, 'id', 1);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        $project = new Project();
        $this->setProperty($project, 'id', 2);

        $template = new Template($project);
        $this->setProperty($template, 'id', 3);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 4);

        $field = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($field, 'id', 5);

        $field->name       = 'Customer reported';
        $field->position   = 1;
        $field->isRequired = false;

        $issue = new Issue($user);
        $this->setProperty($issue, 'id', 6);

        $event = new Event(EventType::ISSUE_EDITED, $issue, $user);
        $this->setProperty($event, 'id', 7);

        $change = new Change($event, $field, 123, 456);
        $this->setProperty($change, 'id', 8);

        self::assertSame($expected, $change->jsonSerialize());
    }
}
