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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldPermission;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldGroupPermission;
use eTraxis\TemplatesDomain\Model\Entity\FieldRolePermission;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \eTraxis\TemplatesDomain\Framework\Controller\ApiFieldsController::setPermissions
 */
class SetPermissionsTest extends TransactionalTestCase
{
    public function testSuccessAll()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
            'groups'     => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertNotEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessRoles()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertNotEmpty($roles);
        self::assertEmpty($groups);
    }

    public function testSuccessGroups()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessNone()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($field);

        $roles = array_filter($field->rolePermissions, function (FieldRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === FieldPermission::READ_WRITE;
        });

        $groups = array_filter($field->groupPermissions, function (FieldGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === FieldPermission::READ_WRITE;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', $field->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $data = [
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/fields/%s/permissions', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
