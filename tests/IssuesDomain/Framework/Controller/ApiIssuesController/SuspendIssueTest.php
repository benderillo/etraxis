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
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\IssuesDomain\Framework\Controller\ApiIssuesController::suspendIssue
 */
class SuspendIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        self::assertFalse($issue->isSuspended);

        $data = [
            'date' => gmdate('Y-m-d', time() + 86400),
        ];

        $uri = sprintf('/api/issues/%s/suspend', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($issue);

        self::assertTrue($issue->isSuspended);
    }

    public function test400()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/suspend', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $data = [
            'date' => gmdate('Y-m-d', time() + 86400),
        ];

        $uri = sprintf('/api/issues/%s/suspend', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 6'], ['id' => 'ASC']);

        $data = [
            'date' => gmdate('Y-m-d', time() + 86400),
        ];

        $uri = sprintf('/api/issues/%s/suspend', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $data = [
            'date' => gmdate('Y-m-d', time() + 86400),
        ];

        $uri = sprintf('/api/issues/%s/suspend', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
