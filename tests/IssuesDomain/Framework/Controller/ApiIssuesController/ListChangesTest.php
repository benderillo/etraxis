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
use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\IssuesDomain\Framework\Controller\ApiIssuesController::listChanges
 */
class ListChangesTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        /** @var Event $event */
        [$event] = $this->doctrine->getRepository(Event::class)->findBy([
            'type'  => EventType::ISSUE_EDITED,
            'issue' => $issue,
        ], [
            'createdAt' => 'ASC',
        ]);

        /** @var Change[] $changes */
        $changes = $this->doctrine->getRepository(Change::class)->findBy(['event' => $event], ['id' => 'ASC']);

        /** @var Field $field */
        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var ListItem $valueNormal */
        $valueNormal = $this->doctrine->getRepository(ListItem::class)->findOneBy([
            'field' => $field,
            'value' => 2,
        ]);

        /** @var ListItem $valueLow */
        $valueLow = $this->doctrine->getRepository(ListItem::class)->findOneBy([
            'field' => $field,
            'value' => 3,
        ]);

        $expected = [
            [
                'id'        => $changes[0]->id,
                'user'      => [
                    'id'       => $event->user->id,
                    'email'    => 'ldoyle@example.com',
                    'fullname' => 'Leland Doyle',
                ],
                'timestamp' => $event->createdAt,
                'field'     => null,
                'old_value' => 'Task 1',
                'new_value' => 'Development task 1',
            ],
            [
                'id'        => $changes[1]->id,
                'user'      => [
                    'id'       => $event->user->id,
                    'email'    => 'ldoyle@example.com',
                    'fullname' => 'Leland Doyle',
                ],
                'timestamp' => $event->createdAt,
                'field'     => [
                    'id'          => $field->id,
                    'name'        => 'Priority',
                    'type'        => FieldType::LIST,
                    'description' => null,
                    'position'    => 1,
                    'required'    => true,
                ],
                'old_value' => [
                    'id'    => $valueLow->id,
                    'value' => 3,
                    'text'  => 'low',
                ],
                'new_value' => [
                    'id'    => $valueNormal->id,
                    'value' => 2,
                    'text'  => 'normal',
                ],
            ],
        ];

        $uri = sprintf('/api/issues/%s/changes', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/changes', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 1'], ['id' => 'ASC']);

        $uri = sprintf('/api/issues/%s/changes', $issue->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/issues/%s/changes', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
