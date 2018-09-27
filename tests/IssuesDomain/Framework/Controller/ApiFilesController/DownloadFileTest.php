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

namespace eTraxis\IssuesDomain\Framework\Controller\ApiFilesController;

use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadFileTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $filename = getcwd() . '/var/' . $file->uuid;
        file_put_contents($filename, 'Lorem ipsum');

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        unlink($filename);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        self::assertSame('text/plain', $response->headers->get('CONTENT_TYPE'));
        self::assertSame('inline; filename=Inventore.pdf', $response->headers->get('CONTENT_DISPOSITION'));
        self::assertSame(11, $response->headers->get('CONTENT_LENGTH'));
    }

    public function test401()
    {
        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/files/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test404removed()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test404missing()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [$file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_GET, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
