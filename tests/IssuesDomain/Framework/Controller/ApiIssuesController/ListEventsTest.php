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

use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListEventsTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $expected = [
            EventType::ISSUE_CREATED    => [],
            EventType::ISSUE_EDITED     => [],
            EventType::FILE_ATTACHED    => [],
            EventType::STATE_CHANGED    => [],
            EventType::ISSUE_ASSIGNED   => [],
            EventType::DEPENDENCY_ADDED => [],
            EventType::PUBLIC_COMMENT   => [],
            EventType::ISSUE_CLOSED     => [],
        ];

        foreach ($issue->events as $event) {
            $expected[$event->type] = $event->jsonSerialize();

            switch ($event->type) {

                case EventType::ISSUE_CREATED:
                case EventType::STATE_CHANGED:
                case EventType::ISSUE_CLOSED:

                    /** @var State $state */
                    $state = $this->doctrine->getRepository(State::class)->find($event->parameter);

                    $expected[$event->type]['state'] = [
                        'id'          => $state->id,
                        'name'        => $state->name,
                        'type'        => $state->type,
                        'responsible' => $state->responsible,
                    ];

                    break;

                case EventType::ISSUE_ASSIGNED:

                    /** @var User $user */
                    $user = $this->doctrine->getRepository(User::class)->find($event->parameter);

                    $expected[$event->type]['assignee'] = [
                        'id'       => $user->id,
                        'email'    => $user->email,
                        'fullname' => $user->fullname,
                    ];

                    break;

                case EventType::FILE_ATTACHED:

                    /** @var File $file */
                    $file = $this->doctrine->getRepository(File::class)->find($event->parameter);

                    $expected[$event->type]['file'] = $file->jsonSerialize();

                    break;

                case EventType::DEPENDENCY_ADDED:

                    /** @var Issue $dependency */
                    $dependency = $this->doctrine->getRepository(Issue::class)->find($event->parameter);

                    $expected[$event->type]['issue'] = $dependency->jsonSerialize();
                    unset($expected[$event->type]['issue']['read_at']);

                    break;
            }
        }

        $uri = sprintf('/api/issues/%s/events', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(array_values($expected), json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/events', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/events', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/issues/%s/events', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
