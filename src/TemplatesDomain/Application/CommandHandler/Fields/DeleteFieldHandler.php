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

use eTraxis\TemplatesDomain\Application\Command\Fields\DeleteFieldCommand;
use eTraxis\TemplatesDomain\Application\Voter\FieldVoter;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteFieldHandler
{
    protected $security;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param FieldRepository               $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, FieldRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteFieldCommand $command): void
    {
        /** @var \eTraxis\TemplatesDomain\Model\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if ($field) {

            if (!$this->security->isGranted(FieldVoter::DELETE_FIELD, $field)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($field);
        }
    }
}
