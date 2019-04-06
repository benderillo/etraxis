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

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Entity\TemplateGroupPermission;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \eTraxis\TemplatesDomain\Application\CommandHandler\Templates\SetGroupsPermissionHandler::handle
 */
class SetGroupsPermissionCommandTest extends TransactionalTestCase
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
            TemplatePermission::CREATE_ISSUES,
            TemplatePermission::VIEW_ISSUES,
        ];

        $after = [
            TemplatePermission::DELETE_FILES,
            TemplatePermission::CREATE_ISSUES,
            TemplatePermission::VIEW_ISSUES,
        ];

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        self::assertSame($before, $this->permissionsToArray($template->groupPermissions, $group->id));

        $command = new SetGroupsPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::DELETE_FILES,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);

        /** Group $group2 */
        [$group2] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::PRIVATE_COMMENTS,
            'groups'     => [
                $group2->id,
            ],
        ]);

        $this->commandBus->handle($command);

        $this->doctrine->getManager()->refresh($template);
        self::assertSame($after, $this->permissionsToArray($template->groupPermissions, $group->id));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::DELETE_FILES,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testUnknownTemplate()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new SetGroupsPermissionCommand([
            'template'   => self::UNKNOWN_ENTITY_ID,
            'permission' => TemplatePermission::DELETE_FILES,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    public function testWrongGroup()
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->loginAs('admin@example.com');

        /** @var Template $template */
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'DESC']);

        $command = new SetGroupsPermissionCommand([
            'template'   => $template->id,
            'permission' => TemplatePermission::DELETE_FILES,
            'groups'     => [
                $group->id,
            ],
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @param TemplateGroupPermission[] $permissions
     * @param int                       $groupId
     *
     * @return string[]
     */
    protected function permissionsToArray(array $permissions, int $groupId): array
    {
        $filtered = array_filter($permissions, function (TemplateGroupPermission $permission) use ($groupId) {
            return $permission->group->id === $groupId;
        });

        $result = array_map(function (TemplateGroupPermission $permission) {
            return $permission->permission;
        }, $filtered);

        sort($result);

        return $result;
    }
}
