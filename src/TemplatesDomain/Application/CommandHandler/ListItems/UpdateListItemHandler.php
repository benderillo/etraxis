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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\ListItems;

use eTraxis\TemplatesDomain\Application\Command\ListItems\UpdateListItemCommand;
use eTraxis\TemplatesDomain\Application\Voter\ListItemVoter;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateListItemHandler
{
    protected $security;
    protected $validator;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param ListItemRepository            $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        ListItemRepository            $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateListItemCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UpdateListItemCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\ListItem $item */
        $item = $this->repository->find($command->item);

        if (!$item) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ListItemVoter::UPDATE_ITEM, $item)) {
            throw new AccessDeniedHttpException();
        }

        $item->value = $command->value;
        $item->text  = $command->text;

        $errors = $this->validator->validate($item);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($item);
    }
}
