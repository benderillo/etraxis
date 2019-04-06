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
use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\IssuesDomain\Framework\Controller\ApiIssuesController::listIssues
 */
class ListIssuesTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('fdooley@example.com');

        $expected = array_map(function (Issue $issue) {

            $json = $issue->jsonSerialize();

            switch ($issue->subject) {
                case 'Development task 1':
                    $json['read_at'] = $issue->closedAt;
                    break;
                case 'Development task 2':
                    $json['read_at'] = $issue->createdAt;
                    break;
                case 'Development task 3':
                    $json['read_at'] = $issue->closedAt;
                    break;
                case 'Development task 5':
                    $json['read_at'] = $issue->createdAt;
                    break;
                case 'Development task 6':
                    $json['read_at'] = $issue->createdAt;
                    break;
            }

            return $json;
        }, $this->doctrine->getRepository(Issue::class)->findBy([], ['id' => 'ASC']));

        $uri = '/api/issues';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        self::assertSame(0, $content['from']);
        self::assertSame(41, $content['to']);
        self::assertSame(42, $content['total']);

        usort($content['data'], function ($issue1, $issue2) {
            return $issue1['id'] - $issue2['id'];
        });

        self::assertSame($expected, $content['data']);
    }

    public function test401()
    {
        $uri = '/api/issues';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
