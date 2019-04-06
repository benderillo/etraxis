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

use eTraxis\TemplatesDomain\Application\Command\Projects\SuspendProjectCommand;
use eTraxis\TemplatesDomain\Application\Voter\ProjectVoter;
use eTraxis\TemplatesDomain\Model\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SuspendProjectHandler
{
    protected $security;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ProjectRepository             $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, ProjectRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param SuspendProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(SuspendProjectCommand $command)
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Project $project */
        $project = $this->repository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $project)) {
            throw new AccessDeniedHttpException();
        }

        if (!$project->isSuspended) {

            $project->isSuspended = true;

            $this->repository->persist($project);
        }
    }
}
