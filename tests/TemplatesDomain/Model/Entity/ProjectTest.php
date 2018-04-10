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

        /** @var \Doctrine\Common\Collections\ArrayCollection $groups */
        $groups = $this->getProperty($project, 'groupsCollection');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $project->groups);
    }
}
