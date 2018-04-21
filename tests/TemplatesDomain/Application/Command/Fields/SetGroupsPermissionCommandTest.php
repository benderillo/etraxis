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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldPermission;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldGroupPermission;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetGroupsPermissionCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $managers */
        [$managers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        /** @var Group $developers */
        [$developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [$support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame(FieldPermission::READ_WRITE, $this->getPermissionByGroup($field->groupPermissions, $managers->id));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByGroup($field->groupPermissions, $developers->id));
        self::assertNull($this->getPermissionByGroup($field->groupPermissions, $support->id));

        $command = new SetGroupsPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_ONLY,
            'groups'     => [
                $managers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($field);

        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByGroup($field->groupPermissions, $managers->id));
        self::assertNull($this->getPermissionByGroup($field->groupPermissions, $developers->id));
        self::assertSame(FieldPermission::READ_ONLY, $this->getPermissionByGroup($field->groupPermissions, $support->id));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'field'      => self::UNKNOWN_ENTITY_ID,
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownGroup()
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        $command = new SetGroupsPermissionCommand([
            'field'      => $field->id,
            'permission' => FieldPermission::READ_WRITE,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    /**
     * @param FieldGroupPermission[] $permissions
     * @param int                    $groupId
     *
     * @return null|string
     */
    protected function getPermissionByGroup(array $permissions, int $groupId): ?string
    {
        $filtered = array_filter($permissions, function (FieldGroupPermission $permission) use ($groupId) {
            return $permission->group->id === $groupId;
        });

        $result = count($filtered) === 1 ? reset($filtered) : null;

        return $result === null ? null : $result->permission;
    }
}
