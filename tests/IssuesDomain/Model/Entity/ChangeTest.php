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
}
