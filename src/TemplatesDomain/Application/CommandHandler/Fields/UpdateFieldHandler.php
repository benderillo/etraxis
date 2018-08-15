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

use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractUpdateFieldCommand;
use eTraxis\TemplatesDomain\Application\Voter\FieldVoter;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateFieldHandler extends AbstractFieldHandler
{
    protected $security;
    protected $validator;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param DecimalValueRepository        $decimalRepository
     * @param StringValueRepository         $stringRepository
     * @param TextValueRepository           $textRepository
     * @param ListItemRepository            $listRepository
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param FieldRepository               $repository
     */
    public function __construct(
        TranslatorInterface           $translator,
        DecimalValueRepository        $decimalRepository,
        StringValueRepository         $stringRepository,
        TextValueRepository           $textRepository,
        ListItemRepository            $listRepository,
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        FieldRepository               $repository
    )
    {
        parent::__construct($translator, $decimalRepository, $stringRepository, $textRepository, $listRepository);

        $this->security   = $security;
        $this->validator  = $validator;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param AbstractUpdateFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function handle(AbstractUpdateFieldCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if (!$field || $field->isRemoved) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::UPDATE_FIELD, $field)) {
            throw new AccessDeniedHttpException();
        }

        $field->name        = $command->name;
        $field->description = $command->description;
        $field->isRequired  = $command->required;

        $field = $this->copyCommandToField($command, $field);

        $errors = $this->validator->validate($field);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($field);
    }
}
