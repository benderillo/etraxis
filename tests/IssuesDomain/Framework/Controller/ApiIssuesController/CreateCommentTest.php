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

use eTraxis\IssuesDomain\Model\Entity\Comment;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateCommentTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('jmueller@example.com');

        /** @var \eTraxis\IssuesDomain\Model\Repository\CommentRepository $repository */
        $repository = $this->doctrine->getRepository(Comment::class);

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $comments = count($repository->findAllByIssue($issue, true));

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        self::assertCount($comments + 1, $repository->findAllByIssue($issue, true));
    }

    public function test400()
    {
        $this->loginAs('jmueller@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [/* skipping */, /* skipping */, $issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 2'], ['id' => 'ASC']);

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', $issue->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('jmueller@example.com');

        $data = [
            'body'    => 'Lorem ipsum',
            'private' => false,
        ];

        $uri = sprintf('/api/issues/%s/comments', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
