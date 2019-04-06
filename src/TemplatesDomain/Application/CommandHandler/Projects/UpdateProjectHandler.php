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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Projects;

use eTraxis\TemplatesDomain\Application\Command\Projects\UpdateProjectCommand;
use eTraxis\TemplatesDomain\Application\Voter\ProjectVoter;
use eTraxis\TemplatesDomain\Model\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateProjectHandler
{
    protected $security;
    protected $validator;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepository             $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepository             $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UpdateProjectCommand $command)
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Project $project */
        $project = $this->repository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project)) {
            throw new AccessDeniedHttpException();
        }

        $project->name        = $command->name;
        $project->description = $command->description;
        $project->isSuspended = $command->suspended;

        $errors = $this->validator->validate($project);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($project);
    }
}
