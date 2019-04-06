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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiMyController;

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\SecurityDomain\Framework\Controller\ApiMyController::getProjects
 */
class GetProjectsTest extends WebTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var Project $projectC */
        /** @var Project $projectD */
        $projectC = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Excepturi']);
        $projectD = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Presto']);

        $expected = [
            $projectC->jsonSerialize(),
            $projectD->jsonSerialize(),
        ];

        $uri = '/api/my/projects';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function testSuccessEmpty()
    {
        $this->loginAs('admin@example.com');

        $expected = [];

        $uri = '/api/my/projects';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($expected, json_decode($response->getContent(), true));
    }

    public function test401()
    {
        $uri = '/api/my/projects';

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
