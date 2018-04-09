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

namespace eTraxis\TemplatesDomain\Model\Repository;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\WebTestCase;

class ProjectRepositoryTest extends WebTestCase
{
    public function testRepository()
    {
        $repository = $this->doctrine->getRepository(Project::class);

        self::assertInstanceOf(ProjectRepository::class, $repository);
    }
}
