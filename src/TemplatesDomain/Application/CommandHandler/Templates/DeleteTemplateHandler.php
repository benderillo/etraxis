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

use eTraxis\TemplatesDomain\Application\Command\Templates\DeleteTemplateCommand;
use eTraxis\TemplatesDomain\Application\Voter\TemplateVoter;
use eTraxis\TemplatesDomain\Model\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteTemplateHandler
{
    protected $security;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TemplateRepository            $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, TemplateRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteTemplateCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteTemplateCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Template $template */
        $template = $this->repository->find($command->template);

        if ($template) {

            if (!$this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $template)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($template);
        }
    }
}
