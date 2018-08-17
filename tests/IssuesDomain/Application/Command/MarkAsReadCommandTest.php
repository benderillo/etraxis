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
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\TransactionalTestCase;

class MarkAsReadCommandTest extends TransactionalTestCase
{
    use ReflectionTrait;

    /** @var \eTraxis\IssuesDomain\Model\Repository\IssueRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Issue::class);
    }

    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var Issue $read */
        [$read] = $this->repository->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->repository->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->repository->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var LastRead $lastRead */
        $lastRead = $this->doctrine->getRepository(LastRead::class)->findOneBy([
            'issue' => $read,
            'user'  => $user,
        ]);

        self::assertGreaterThan(1, time() - $lastRead->readAt);

        $count = count($this->doctrine->getRepository(LastRead::class)->findAll());

        $command = new MarkAsReadCommand([
            'issues' => [
                $read->id,
                $unread->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($lastRead);

        self::assertCount($count + 1, $this->doctrine->getRepository(LastRead::class)->findAll());
        self::assertLessThanOrEqual(1, time() - $lastRead->readAt);
    }
}
