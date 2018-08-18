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

namespace eTraxis\IssuesDomain\Model\Repository;

use eTraxis\IssuesDomain\Model\Entity\Dependency;
use eTraxis\Tests\TransactionalTestCase;

class DependencyRepositoryTest extends TransactionalTestCase
{
    /** @var DependencyRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Dependency::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(DependencyRepository::class, $this->repository);
    }
}
