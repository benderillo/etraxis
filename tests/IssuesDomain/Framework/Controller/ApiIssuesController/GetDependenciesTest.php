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

class GetDependenciesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('tmarquardt@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        /** @var Issue $dependency */
        [$dependency] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 3'], ['id' => 'ASC']);

        $expected = [
            $dependency->jsonSerialize(),
        ];

        $expected[0]['read_at'] = $dependency->createdAt;

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        self::assertSame(0, $content['from']);
        self::assertSame(0, $content['to']);
        self::assertSame(1, $content['total']);

        self::assertSame($expected, $content['data']);
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/dependencies', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('tmarquardt@example.com');

        $uri = sprintf('/api/issues/%s/dependencies', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
