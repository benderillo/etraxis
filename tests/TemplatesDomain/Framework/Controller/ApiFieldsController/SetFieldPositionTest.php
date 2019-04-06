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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController::setFieldPosition
 */
class SetFieldPositionTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature'], ['id' => 'ASC']);
        self::assertNotSame(2, $field->position);

        $data = [
            'position' => 2,
        ];

        $uri = sprintf('/api/fields/%s/position', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        self::assertSame(2, $field->position);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        $uri = sprintf('/api/fields/%s/position', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        $data = [
            'position' => 2,
        ];

        $uri = sprintf('/api/fields/%s/position', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature'], ['id' => 'ASC']);

        $data = [
            'position' => 2,
        ];

        $uri = sprintf('/api/fields/%s/position', $field->id);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'position' => 2,
        ];

        $uri = sprintf('/api/fields/%s/position', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
