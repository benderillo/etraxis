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

namespace eTraxis\SecurityDomain\Model\Entity;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();
        $this->setProperty($project, 'id', 1);

        $group = new Group($project);
        self::assertSame($project, $this->getProperty($group, 'project'));

        $group = new Group();
        self::assertNull($this->getProperty($group, 'project'));
    }

    public function testMembers()
    {
        $group = new Group(new Project());
        self::assertSame([], $group->members);

        $user1 = new User();
        $user2 = new User();

        $this->setProperty($user1, 'id', 1);
        $this->setProperty($user2, 'id', 2);

        $group->addMember($user1);
        $group->addMember($user2);

        self::assertSame([$user1, $user2], $group->members);

        $group->removeMember($user1);

        self::assertSame([$user2], $group->members);
    }

    public function testIsGlobal()
    {
        $group = new Group(new Project());
        self::assertFalse($group->isGlobal);

        $group = new Group();
        self::assertTrue($group->isGlobal);
    }
}
