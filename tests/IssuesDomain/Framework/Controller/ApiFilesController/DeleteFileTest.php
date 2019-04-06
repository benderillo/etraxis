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

/**
 * @covers \eTraxis\IssuesDomain\Framework\Controller\ApiFilesController::deleteFile
 */
class DeleteFileTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);
        self::assertFalse($file->isRemoved);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($file);

        self::assertTrue($file->isRemoved);
    }

    public function test401()
    {
        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Inventore.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('ldoyle@example.com');

        $uri = sprintf('/api/files/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test404removed()
    {
        $this->loginAs('ldoyle@example.com');

        /** @var File $file */
        [/* skipping */, /* skipping */, $file] = $this->doctrine->getRepository(File::class)->findBy(['name' => 'Possimus sapiente.pdf'], ['id' => 'ASC']);

        $uri = sprintf('/api/files/%s', $file->id);

        $response = $this->json(Request::METHOD_DELETE, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
