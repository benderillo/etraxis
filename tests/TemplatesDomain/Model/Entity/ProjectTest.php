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

use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testConstructor()
    {
        $project = new Project();

        self::assertLessThanOrEqual(1, time() - $project->createdAt);
    }
}
