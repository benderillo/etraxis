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
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $project = new Project();

        self::assertLessThanOrEqual(1, time() - $project->createdAt);
    }

    public function testGroups()
    {
        $project = new Project();
        self::assertSame([], $project->groups);

        /** @var \Doctrine\Common\Collections\ArrayCollection $collection */
        $collection = $this->getProperty($project, 'groupsCollection');

        $group1 = new Group();
        $group2 = new Group();

        $this->setProperty($group1, 'id', 1);
        $this->setProperty($group2, 'id', 2);

        $collection->add($group1);
        $collection->add($group2);

        self::assertSame([$group1, $group2], $project->groups);

        $collection->removeElement($group1);

        self::assertSame([$group2], $project->groups);
    }
}
