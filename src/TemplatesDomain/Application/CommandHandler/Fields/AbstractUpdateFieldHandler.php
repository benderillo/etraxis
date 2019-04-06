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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractUpdateFieldCommand;
use eTraxis\TemplatesDomain\Application\Voter\FieldVoter;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract "Update field" command handler.
 */
abstract class AbstractUpdateFieldHandler
{
    protected $security;
    protected $translator;
    protected $validator;
    protected $repository;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param TranslatorInterface           $translator
     * @param ValidatorInterface            $validator
     * @param FieldRepository               $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        TranslatorInterface           $translator,
        ValidatorInterface            $validator,
        FieldRepository               $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->translator = $translator;
        $this->validator  = $validator;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Copies field-specific parameters from create/update command to specified field.
     *
     * @param TranslatorInterface    $translator
     * @param EntityManagerInterface $manager
     * @param AbstractFieldCommand   $command
     * @param Field                  $field
     *
     * @return Field Updated field entity.
     */
    abstract protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field;

    /**
     * Command handler.
     *
     * @param AbstractUpdateFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    protected function update(AbstractUpdateFieldCommand $command): void
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

        $field = $this->copyCommandToField($this->translator, $this->manager, $command, $field);

        $errors = $this->validator->validate($field);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($field);
    }
}
