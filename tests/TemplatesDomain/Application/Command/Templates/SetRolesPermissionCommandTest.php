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

namespace eTraxis\TemplatesDomain\Application\Command\Templates;

use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Entity\TemplateRolePermission;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetRolesPermissionCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $before = [
            TemplatePermission::ADD_COMMENTS,
            TemplatePermission::ADD_DEPENDENCIES,
            TemplatePermission::REMOVE_DEPENDENCIES,
            TemplatePermission::ATTACH_FILES,
            TemplatePermission::EDIT_ISSUES,
        ];

        $after = [
            TemplatePermission::ADD_COMMENTS,
            TemplatePermission::PRIVATE_COMMENTS,
            TemplatePermission::ADD_DEPENDENCIES,
            TemplatePermission::REMOVE_DEPENDENCIES,
            TemplatePermission::EDIT_ISSUES,
        ];

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertSame($before, $this->permissionsToArray($template->rolePermissions, SystemRole::AUTHOR));

        $command = new SetRolesPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::PRIVATE_COMMENTS,
            'roles'      => [
                SystemRole::AUTHOR,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);

        $command = new SetRolesPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::ATTACH_FILES,
            'roles'      => [
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertSame($after, $this->permissionsToArray($template->rolePermissions, SystemRole::AUTHOR));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $command = new SetRolesPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::PRIVATE_COMMENTS,
            'roles'      => [
                SystemRole::AUTHOR,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetRolesPermissionCommand([
            'template'   => self::UNKNOWN_ENTITY_ID,
            'permission' => TemplatePermission::PRIVATE_COMMENTS,
            'roles'      => [
                SystemRole::AUTHOR,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @param TemplateRolePermission[] $permissions
     * @param string                   $role
     *
     * @return string[]
     */
    protected function permissionsToArray(array $permissions, string $role): array
    {
        $filtered = array_filter($permissions, function (TemplateRolePermission $permission) use ($role) {
            return $permission->role === $role;
        });

        $result = array_map(function (TemplateRolePermission $permission) {
            return $permission->permission;
        }, $filtered);

        sort($result);

        return $result;
    }
}
