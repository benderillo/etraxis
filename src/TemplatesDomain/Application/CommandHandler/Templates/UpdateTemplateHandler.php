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

use eTraxis\TemplatesDomain\Application\Command\Templates\UpdateTemplateCommand;
use eTraxis\TemplatesDomain\Application\Voter\TemplateVoter;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateTemplateHandler
{
    protected $security;
    protected $validator;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TemplateRepository            $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TemplateRepository            $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UpdateTemplateCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template)) {
            throw new AccessDeniedHttpException();
        }

        $template->name        = $command->name;
        $template->prefix      = $command->prefix;
        $template->description = $command->description;
        $template->criticalAge = $command->criticalAge;
        $template->frozenTime  = $command->frozenTime;

        $errors = $this->validator->validate($template);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($template);
    }
}
