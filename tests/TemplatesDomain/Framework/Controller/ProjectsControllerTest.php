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

namespace eTraxis\TemplatesDomain\Framework\Controller;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectsControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $uri = '/admin/projects';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/login'));

        $this->loginAs('artem@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isForbidden());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());
    }

    public function testPermissions()
    {
        /** @var Project $project */
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $expected = [
            'create'  => true,
            'update'  => true,
            'delete'  => false,
            'suspend' => true,
            'resume'  => true,
        ];

        $uri = sprintf('/admin/projects/permissions/%s', $project->id);

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $this->loginAs('artem@example.com');

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->loginAs('admin@example.com');

        $response = $this->json(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }
}
