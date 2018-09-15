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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiTemplatesController;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Entity\TemplateGroupPermission;
use eTraxis\TemplatesDomain\Model\Entity\TemplateRolePermission;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTest extends TransactionalTestCase
{
    public function testSuccessAll()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
            'groups'     => [
                $group->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertNotEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessRoles()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertNotEmpty($roles);
        self::assertEmpty($groups);
    }

    public function testSuccessGroups()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'groups'     => [
                $group->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessNone()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['description' => 'ASC']);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        $roles = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) {
            return $permission->role === SystemRole::AUTHOR && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        $groups = array_filter($template->groupPermissions, function (TemplateGroupPermission $permission) use ($group) {
            return $permission->group === $group && $permission->permission === TemplatePermission::DELETE_ISSUES;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);
    }

    public function test400()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $data = [
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['description' => 'ASC']);

        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/templates/%s/permissions', $template->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $data = [
            'permission' => TemplatePermission::DELETE_ISSUES,
            'roles'      => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/permissions', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
