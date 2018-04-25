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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class IssueTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $user = new User();
        $this->setProperty($user, 'id', 1);

        $issue = new Issue($user);

        $createdAt = $this->getProperty($issue, 'createdAt');
        $changedAt = $this->getProperty($issue, 'changedAt');

        self::assertSame($user, $issue->author);
        self::assertLessThanOrEqual(1, time() - $createdAt);
        self::assertSame($createdAt, $changedAt);
    }

    public function testFullId()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);
        $this->setProperty($template, 'prefix', 'bug');

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        $this->setProperty($issue, 'id', 4);
        self::assertSame('bug-004', $issue->fullId);

        $this->setProperty($issue, 'id', 1234);
        self::assertSame('bug-1234', $issue->fullId);
    }

    public function testTouch()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $issue = new Issue(new User());
        $this->setProperty($issue, 'changedAt', 0);

        $changedAt = $this->getProperty($issue, 'changedAt');
        self::assertGreaterThan(1, time() - $changedAt);

        $issue->touch();

        $changedAt = $this->getProperty($issue, 'changedAt');
        self::assertLessThanOrEqual(1, time() - $changedAt);
    }

    public function testProject()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        self::assertSame($project, $issue->project);
    }

    public function testTemplate()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 3);

        $issue = new Issue(new User());

        $issue->state = $state;

        self::assertSame($template, $issue->template);
    }

    public function testState()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 3);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($final, 'id', 4);

        $issue = new Issue(new User());

        $issue->state = $initial;
        self::assertSame($initial, $issue->state);

        $issue->state = $final;
        self::assertSame($final, $issue->state);
    }

    public function testStateException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown state: bar');

        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $template2 = new Template($project);
        $this->setProperty($template2, 'id', 3);

        $state = new State($template, StateType::INITIAL);
        $this->setProperty($state, 'id', 4);
        $this->setProperty($state, 'name', 'foo');

        $state2 = new State($template2, StateType::FINAL);
        $this->setProperty($state2, 'id', 5);
        $this->setProperty($state2, 'name', 'bar');

        $issue = new Issue(new User());

        $issue->state = $state;
        $issue->state = $state2;
    }

    public function testIsClosed()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $template = new Template($project);
        $this->setProperty($template, 'id', 2);

        $initial = new State($template, StateType::INITIAL);
        $this->setProperty($initial, 'id', 3);

        $final = new State($template, StateType::FINAL);
        $this->setProperty($final, 'id', 4);

        $issue = new Issue(new User());
        self::assertFalse($issue->isClosed);

        $issue->state = $initial;
        self::assertFalse($issue->isClosed);

        $issue->state = $final;
        self::assertTrue($issue->isClosed);

        $issue->state = $initial;
        self::assertFalse($issue->isClosed);
    }

    public function testEvents()
    {
        $issue = new Issue(new User());
        self::assertSame([], $issue->events);

        /** @var \Doctrine\Common\Collections\ArrayCollection $events */
        $events = $this->getProperty($issue, 'eventsCollection');
        $events->add('Event A');
        $events->add('Event B');

        self::assertSame(['Event A', 'Event B'], $issue->events);
    }

    public function testValues()
    {
        $issue = new Issue(new User());
        self::assertSame([], $issue->values);

        /** @var \Doctrine\Common\Collections\ArrayCollection $values */
        $values = $this->getProperty($issue, 'valuesCollection');
        $values->add('Value A');
        $values->add('Value B');

        self::assertSame(['Value A', 'Value B'], $issue->values);
    }
}
