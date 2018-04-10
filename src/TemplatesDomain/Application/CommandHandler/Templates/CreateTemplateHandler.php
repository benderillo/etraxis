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

use eTraxis\TemplatesDomain\Application\Command\Templates\CreateTemplateCommand;
use eTraxis\TemplatesDomain\Application\Voter\TemplateVoter;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Repository\ProjectRepository;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateTemplateHandler
{
    protected $security;
    protected $validator;
    protected $projectRepository;
    protected $templateRepository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ProjectRepository             $projectRepository
     * @param TemplateRepository            $templateRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ProjectRepository             $projectRepository,
        TemplateRepository            $templateRepository
    )
    {
        $this->security           = $security;
        $this->validator          = $validator;
        $this->projectRepository  = $projectRepository;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Command handler.
     *
     * @param CreateTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return Template
     */
    public function handle(CreateTemplateCommand $command): Template
    {
        /** @var \eTraxis\TemplatesDomain\Model\Entity\Project $project */
        $project = $this->projectRepository->find($command->project);

        if (!$project) {
            throw new NotFoundHttpException('Unknown project.');
        }

        if (!$this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project)) {
            throw new AccessDeniedHttpException();
        }

        $template = new Template($project);

        $template->name        = $command->name;
        $template->prefix      = $command->prefix;
        $template->description = $command->description;
        $template->criticalAge = $command->criticalAge;
        $template->frozenTime  = $command->frozenTime;
        $template->isLocked    = true;

        $errors = $this->validator->validate($template);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->templateRepository->persist($template);

        return $template;
    }
}
