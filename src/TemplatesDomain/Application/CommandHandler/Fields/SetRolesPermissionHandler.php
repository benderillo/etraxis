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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Fields;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\SetRolesPermissionCommand;
use eTraxis\TemplatesDomain\Application\Voter\FieldVoter;
use eTraxis\TemplatesDomain\Model\Entity\FieldRolePermission;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetRolesPermissionHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param FieldRepository               $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        FieldRepository               $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param SetRolesPermissionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(SetRolesPermissionCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if (!$field) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $field)) {
            throw new AccessDeniedHttpException();
        }

        // Remove all roles which are supposed to not be granted with specified permission, but they currently are.
        $permissions = array_filter($field->rolePermissions, function (FieldRolePermission $permission) use ($command) {
            return $permission->permission === $command->permission;
        });

        foreach ($permissions as $permission) {
            if (!in_array($permission->role, $command->roles, true)) {
                $this->manager->remove($permission);
            }
        }

        // Update all roles which are supposed to be granted with specified permission, but they currently are granted with another permission.
        foreach ($field->rolePermissions as $permission) {
            if (in_array($permission->role, $command->roles, true) && $permission->permission !== $command->permission) {
                $permission->permission = $command->permission;
                $this->manager->persist($permission);
            }
        }

        // Add all roles which are supposed to be granted with specified permission, but they currently are not.
        $existingRoles = array_map(function (FieldRolePermission $permission) {
            return $permission->role;
        }, $field->rolePermissions);

        foreach ($command->roles as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $permission = new FieldRolePermission($field, $role, $command->permission);
                $this->manager->persist($permission);
            }
        }
    }
}
