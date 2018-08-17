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

use eTraxis\IssuesDomain\Model\Entity\Watcher;
use eTraxis\Tests\TransactionalTestCase;

class WatcherRepositoryTest extends TransactionalTestCase
{
    /** @var WatcherRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Watcher::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(WatcherRepository::class, $this->repository);
    }
}
