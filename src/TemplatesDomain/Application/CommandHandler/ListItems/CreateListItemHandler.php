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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\ListItems\CreateListItemCommand;
use eTraxis\TemplatesDomain\Application\Voter\ListItemVoter;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateListItemHandler
{
    protected $security;
    protected $validator;
    protected $fieldRepository;
    protected $itemRepository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param FieldRepository               $fieldRepository
     * @param ListItemRepository            $itemRepository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        FieldRepository               $fieldRepository,
        ListItemRepository            $itemRepository,
        EntityManagerInterface        $manager
    )
    {
        $this->security        = $security;
        $this->validator       = $validator;
        $this->fieldRepository = $fieldRepository;
        $this->itemRepository  = $itemRepository;
        $this->manager         = $manager;
    }

    /**
     * Command handler.
     *
     * @param CreateListItemCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return ListItem
     */
    public function handle(CreateListItemCommand $command): ListItem
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Field $field */
        $field = $this->fieldRepository->find($command->field);

        if (!$field) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(ListItemVoter::CREATE_ITEM, $field)) {
            throw new AccessDeniedHttpException();
        }

        $item = new ListItem($field);

        $item->value = $command->value;
        $item->text  = $command->text;

        $errors = $this->validator->validate($item);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->itemRepository->persist($item);

        return $item;
    }
}
