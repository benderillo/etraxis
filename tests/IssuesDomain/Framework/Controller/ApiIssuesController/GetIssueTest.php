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
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetIssueTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        /** @var User $author */
        $author = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'lucas.oconnell@example.com']);

        $expected = [
            'id'           => $issue->id,
            'subject'      => 'Support request 6',
            'created_at'   => $issue->createdAt,
            'changed_at'   => $issue->changedAt,
            'closed_at'    => null,
            'author'       => [
                'id'       => $author->id,
                'email'    => 'lucas.oconnell@example.com',
                'fullname' => 'Lucas O\'Connell',
            ],
            'state'        => [
                'id'          => $issue->state->id,
                'template'    => [
                    'id'          => $issue->state->template->id,
                    'project'     => [
                        'id'          => $issue->state->template->project->id,
                        'name'        => 'Distinctio',
                        'description' => 'Project A',
                        'created'     => $issue->state->template->project->createdAt,
                        'suspended'   => true,
                    ],
                    'name'        => 'Support',
                    'prefix'      => 'req',
                    'description' => 'Support Request A',
                    'critical'    => 3,
                    'frozen'      => 7,
                    'locked'      => true,
                ],
                'name'        => 'Submitted',
                'type'        => 'initial',
                'responsible' => 'keep',
                'next'        => null,
            ],
            'responsible'  => null,
            'is_cloned'    => false,
            'origin'       => null,
            'age'          => $issue->age,
            'is_critical'  => true,
            'is_suspended' => false,
            'resumes_at'   => null,
            'is_closed'    => false,
            'is_frozen'    => false,
            'read_at'      => null,
        ];

        $uri = sprintf('/api/issues/%s', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        $uri = sprintf('/api/issues/%s', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Support request 6']);

        $uri = sprintf('/api/issues/%s', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('nhills@example.com');

        $uri = sprintf('/api/issues/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
