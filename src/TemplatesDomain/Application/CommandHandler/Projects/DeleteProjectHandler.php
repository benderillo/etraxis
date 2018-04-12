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

use eTraxis\TemplatesDomain\Application\Command\Projects\DeleteProjectCommand;
use eTraxis\TemplatesDomain\Application\Voter\ProjectVoter;
use eTraxis\TemplatesDomain\Model\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteProjectHandler
{
    protected $security;
    protected $repository;

    /**
     * Dependency Injection constructor.
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
     * @param DeleteProjectCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteProjectCommand $command)
    {
        /** @var \eTraxis\TemplatesDomain\Model\Entity\Project $project */
        $project = $this->repository->find($command->project);

        if ($project) {

            if (!$this->security->isGranted(ProjectVoter::DELETE_PROJECT, $project)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($project);
        }
    }
}
