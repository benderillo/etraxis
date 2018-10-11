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

namespace eTraxis\IssuesDomain\Application\Command;

use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Entity\LastRead;
use eTraxis\Tests\TransactionalTestCase;

class MarkAsUnreadCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\IssuesDomain\Model\Repository\IssueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('fdooley@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Development task 4'], ['id' => 'ASC']);

        $count = count($this->doctrine->getRepository(LastRead::class)->findAll());

        $command = new MarkAsUnreadCommand([
            'issues' => [
                $read->id,
                $unread->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandBus->handle($command);

        self::assertCount($count - 1, $this->doctrine->getRepository(LastRead::class)->findAll());
    }
}
