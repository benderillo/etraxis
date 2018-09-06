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
use eTraxis\TemplatesDomain\Application\Command\Fields as Command;
use eTraxis\TemplatesDomain\Application\Voter\FieldVoter;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateFieldHandler extends AbstractFieldHandler
{
    protected $types = [
        Command\CreateCheckboxFieldCommand::class => FieldType::CHECKBOX,
        Command\CreateDateFieldCommand::class     => FieldType::DATE,
        Command\CreateDecimalFieldCommand::class  => FieldType::DECIMAL,
        Command\CreateDurationFieldCommand::class => FieldType::DURATION,
        Command\CreateIssueFieldCommand::class    => FieldType::ISSUE,
        Command\CreateListFieldCommand::class     => FieldType::LIST,
        Command\CreateNumberFieldCommand::class   => FieldType::NUMBER,
        Command\CreateStringFieldCommand::class   => FieldType::STRING,
        Command\CreateTextFieldCommand::class     => FieldType::TEXT,
    ];

    protected $security;
    protected $validator;
    protected $stateRepository;
    protected $fieldRepository;

    /**
     * Dependency Injection constructor.
     *
     * @param TranslatorInterface           $translator
     * @param EntityManagerInterface        $manager
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param StateRepository               $stateRepository
     * @param FieldRepository               $fieldRepository
     */
    public function __construct(
        TranslatorInterface           $translator,
        EntityManagerInterface        $manager,
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        StateRepository               $stateRepository,
        FieldRepository               $fieldRepository
    )
    {
        parent::__construct($translator, $manager);

        $this->security        = $security;
        $this->validator       = $validator;
        $this->stateRepository = $stateRepository;
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * Command handler.
     *
     * @param Command\AbstractCreateFieldCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     *
     * @return Field
     */
    public function handle(Command\AbstractCreateFieldCommand $command): Field
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $state */
        $state = $this->stateRepository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::CREATE_FIELD, $state)) {
            throw new AccessDeniedHttpException();
        }

        $class = get_class($command);
        $field = new Field($state, $this->types[$class]);

        $field->name        = $command->name;
        $field->description = $command->description;
        $field->isRequired  = $command->required;
        $field->position    = count($state->fields) + 1;

        $field = $this->copyCommandToField($command, $field);

        $errors = $this->validator->validate($field);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->fieldRepository->persist($field);

        return $field;
    }
}
