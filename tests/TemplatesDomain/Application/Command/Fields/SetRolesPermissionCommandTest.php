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

namespace eTraxis\TemplatesDomain\Application\Command\Fields;

use eTraxis\TemplatesDomain\Model\Dictionary\FieldPermission;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldRolePermission;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetRolesPermissionCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccessWithRemove()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::ANYONE));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByRole($field->rolePermissions, SystemRole::AUTHOR));
        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::RESPONSIBLE));

        $command = new SetRolesPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_ONLY,
            'roles'      => [
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::ANYONE));
        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::AUTHOR));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByRole($field->rolePermissions, SystemRole::RESPONSIBLE));
    }

    public function testSuccessWithKeep()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::ANYONE));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByRole($field->rolePermissions, SystemRole::AUTHOR));
        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::RESPONSIBLE));

        $command = new SetRolesPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_ONLY,
            'roles'      => [
                SystemRole::AUTHOR,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::ANYONE));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByRole($field->rolePermissions, SystemRole::AUTHOR));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByRole($field->rolePermissions, SystemRole::RESPONSIBLE));
    }

    public function testSuccessWithReplace()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::ANYONE));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByRole($field->rolePermissions, SystemRole::AUTHOR));
        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::RESPONSIBLE));

        $command = new SetRolesPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_WRITE,
            'roles'      => [
                SystemRole::AUTHOR,
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertNull($this->getPermissionByRole($field->rolePermissions, SystemRole::ANYONE));
        self::assertSame(FieldPermission::READ_WRITE, $this->getPermissionByRole($field->rolePermissions, SystemRole::AUTHOR));
        self::assertSame(FieldPermission::READ_WRITE, $this->getPermissionByRole($field->rolePermissions, SystemRole::RESPONSIBLE));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $command = new SetRolesPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_ONLY,
            'roles'      => [
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetRolesPermissionCommand([
            'field'      => self::UNKNOWN_ENTITY_ID,
            'permission' => FieldPermission::READ_ONLY,
            'roles'      => [
                SystemRole::RESPONSIBLE,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    /**
     * @param FieldRolePermission[] $permissions
     * @param string                $role
     *
     * @return null|string
     */
    protected function getPermissionByRole(array $permissions, string $role): ?string
    {
        $filtered = array_filter($permissions, function (FieldRolePermission $permission) use ($role) {
            return $permission->role === $role;
        });

        $result = count($filtered) === 1 ? reset($filtered) : null;

        return $result === null ? null : $result->permission;
    }
}
