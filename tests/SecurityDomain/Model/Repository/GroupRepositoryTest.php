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

namespace eTraxis\SecurityDomain\Model\Repository;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\Tests\WebTestCase;

class GroupRepositoryTest extends WebTestCase
{
    public function testRepository()
    {
        $repository = $this->doctrine->getRepository(Group::class);

        self::assertInstanceOf(GroupRepository::class, $repository);
    }
}