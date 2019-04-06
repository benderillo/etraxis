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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Templates;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Templates\SetRolesPermissionCommand;
use eTraxis\TemplatesDomain\Application\Voter\TemplateVoter;
use eTraxis\TemplatesDomain\Model\Entity\TemplateRolePermission;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
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
     * @param TemplateRepository            $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TemplateRepository            $repository,
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
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template)) {
            throw new AccessDeniedHttpException();
        }

        // Remove all roles which are supposed to not be granted with specified permission, but they currently are.
        $permissions = array_filter($template->rolePermissions, function (TemplateRolePermission $permission) use ($command) {
            return $permission->permission === $command->permission;
        });

        foreach ($permissions as $permission) {
            if (!in_array($permission->role, $command->roles, true)) {
                $this->manager->remove($permission);
            }
        }

        // Add all roles which are supposed to be granted with specified permission, but they currently are not.
        $existingRoles = array_map(function (TemplateRolePermission $permission) {
            return $permission->role;
        }, $permissions);

        foreach ($command->roles as $role) {
            if (!in_array($role, $existingRoles, true)) {
                $permission = new TemplateRolePermission($template, $role, $command->permission);
                $this->manager->persist($permission);
            }
        }
    }
}
