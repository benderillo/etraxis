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

class UpdateFieldTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\CheckboxInterface $facade */
        $facade = $field->getFacade($manager);
        self::assertFalse($facade->getDefaultValue());

        $data = [
            'name'         => $field->name,
            'required'     => $field->isRequired,
            'defaultValue' => true,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        self::assertTrue($facade->getDefaultValue());
    }

    public function test400()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'         => $field->name,
            'required'     => $field->isRequired,
            'defaultValue' => true,
        ];

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'         => $field->name,
            'required'     => $field->isRequired,
            'defaultValue' => true,
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'         => $field->name,
            'required'     => $field->isRequired,
            'defaultValue' => true,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'New feature']);

        $data = [
            'name'         => 'Priority',
            'required'     => $field->isRequired,
            'defaultValue' => true,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/fields/%s', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}