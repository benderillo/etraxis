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

namespace eTraxis\SecurityDomain\Application\CommandHandler\Groups;

use eTraxis\SecurityDomain\Application\Command\Groups\CreateGroupCommand;
use eTraxis\SecurityDomain\Application\Voter\GroupVoter;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Repository\GroupRepository;
use eTraxis\TemplatesDomain\Model\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateGroupHandler
{
    protected $security;
    protected $validator;
    protected $projectRepository;
    protected $groupRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepository             $projectRepository
     * @param GroupRepository               $groupRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepository             $projectRepository,
        GroupRepository               $groupRepository
    )
    {
        $this->security          = $security;
        $this->validator         = $validator;
        $this->projectRepository = $projectRepository;
        $this->groupRepository   = $groupRepository;
    }

    /**
     * Command handler.
     *
     * @param CreateGroupCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return Group
     */
    public function handle(CreateGroupCommand $command): Group
    {
        if (!$this->security->isGranted(GroupVoter::CREATE_GROUP)) {
            throw new AccessDeniedHttpException();
        }

        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Project $project */
        $project = null;

        if ($command->project) {

            $project = $this->projectRepository->find($command->project);

            if (!$project) {
                throw new NotFoundHttpException();
            }
        }

        $group = new Group($project);

        $group->name        = $command->name;
        $group->description = $command->description;

        $errors = $this->validator->validate($group);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->groupRepository->persist($group);

        return $group;
    }
}
