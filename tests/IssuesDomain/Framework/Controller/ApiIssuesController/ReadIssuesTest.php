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

namespace eTraxis\IssuesDomain\Framework\Controller\ApiIssuesController;

use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Entity\LastRead;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\IssuesDomain\Framework\Controller\ApiIssuesController::readIssues
 */
class ReadIssuesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'tmarquardt@example.com']);

        /** @var Issue $read */
        [$read] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        self::assertNotNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $read, 'user' => $user]));
        self::assertNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $unread, 'user' => $user]));
        self::assertNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $forbidden, 'user' => $user]));

        $data = [
            'issues' => [
                $read->id,
                $unread->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $uri = '/api/issues/read';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        self::assertNotNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $read, 'user' => $user]));
        self::assertNotNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $unread, 'user' => $user]));
        self::assertNull($this->doctrine->getRepository(LastRead::class)->findOneBy(['issue' => $forbidden, 'user' => $user]));
    }

    public function test401()
    {
        /** @var Issue $read */
        [$read] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 1'], ['id' => 'ASC']);

        /** @var Issue $unread */
        [$unread] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 4'], ['id' => 'ASC']);

        /** @var Issue $forbidden */
        [$forbidden] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $data = [
            'issues' => [
                $read->id,
                $unread->id,
                $forbidden->id,
                self::UNKNOWN_ENTITY_ID,
            ],
        ];

        $uri = '/api/issues/read';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
