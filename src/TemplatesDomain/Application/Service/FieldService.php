<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  You should have received a copy of the GNU General Public License
//  along with the file. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\TemplatesDomain\Application\Service;

use eTraxis\TemplatesDomain\Application\Command\Fields as Command;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Service to process fields of any type.
 */
class FieldService implements FieldServiceInterface
{
    protected $translator;
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;

    /**
     * Dependency Injection constructor.
     *
     * @param TranslatorInterface    $translator
     * @param DecimalValueRepository $decimalRepository
     * @param StringValueRepository  $stringRepository
     * @param TextValueRepository    $textRepository
     * @param ListItemRepository     $listRepository
     */
    public function __construct(
        TranslatorInterface    $translator,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository
    )
    {
        $this->translator        = $translator;
        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationConstraints(Field $field, ?int $timestamp = null): array
    {
        switch ($field->type) {

            case FieldType::NUMBER:
                return $field->asNumber()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::DECIMAL:
                return $field->asDecimal($this->decimalRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::STRING:
                return $field->asString($this->stringRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::TEXT:
                return $field->asText($this->textRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::CHECKBOX:
                return $field->asCheckbox()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::LIST:
                return $field->asList($this->listRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::ISSUE:
                return $field->asIssue()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::DATE:
                return $field->asDate()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::DURATION:
                return $field->asDuration()->getValidationConstraints($this->translator, $timestamp);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function copyCommandToField(Command\AbstractFieldCommand $command, Field $field): Field
    {
        $handlers = [
            FieldType::CHECKBOX => 'copyAsCheckbox',
            FieldType::DATE     => 'copyAsDate',
            FieldType::DECIMAL  => 'copyAsDecimal',
            FieldType::DURATION => 'copyAsDuration',
            FieldType::ISSUE    => 'copyAsIssue',
            FieldType::LIST     => 'copyAsList',
            FieldType::NUMBER   => 'copyAsNumber',
            FieldType::STRING   => 'copyAsString',
            FieldType::TEXT     => 'copyAsText',
        ];

        $handler = $handlers[$field->type];

        return $this->{$handler}($command, $field);
    }

    /**
     * Copies field-specific parameters from create/update command to specified "checkbox" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsCheckbox(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\CheckboxCommandTrait $command */
        $facade = $field->asCheckbox();

        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "date" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsDate(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DateCommandTrait $command */
        $facade = $field->asDate();

        if ($command->minimumValue > $command->maximumValue) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if ($command->defaultValue < $command->minimumValue || $command->defaultValue > $command->maximumValue) {

                $message = $this->translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimumValue,
                    '%maximum%' => $command->maximumValue,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimumValue);
        $facade->setMaximumValue($command->maximumValue);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "decimal" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsDecimal(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DecimalCommandTrait $command */
        $facade = $field->asDecimal($this->decimalRepository);

        if (bccomp($command->minimumValue, $command->maximumValue, DecimalValue::PRECISION) > 0) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if (bccomp($command->defaultValue, $command->minimumValue, DecimalValue::PRECISION) < 0 ||
                bccomp($command->defaultValue, $command->maximumValue, DecimalValue::PRECISION) > 0)
            {

                $message = $this->translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimumValue,
                    '%maximum%' => $command->maximumValue,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimumValue);
        $facade->setMaximumValue($command->maximumValue);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "duration" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsDuration(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DurationCommandTrait $command */
        $facade = $field->asDuration();

        $minimumValue = $facade->toNumber($command->minimumValue);
        $maximumValue = $facade->toNumber($command->maximumValue);

        if ($minimumValue > $maximumValue) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            $default = $facade->toNumber($command->defaultValue);

            if ($default < $minimumValue || $default > $maximumValue) {

                $message = $this->translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimumValue,
                    '%maximum%' => $command->maximumValue,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimumValue);
        $facade->setMaximumValue($command->maximumValue);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "issue" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsIssue(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\IssueCommandTrait $command */

        // NOP

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "list" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsList(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\ListCommandTrait $command */
        $facade = $field->asList($this->listRepository);

        if (get_parent_class($command) === Command\AbstractUpdateFieldCommand::class) {

            /** @var Command\UpdateListFieldCommand $command */
            if ($command->defaultValue === null) {
                $facade->setDefaultValue(null);
            }
            else {
                /** @var null|\eTraxis\TemplatesDomain\Model\Entity\ListItem $item */
                $item = $this->listRepository->find($command->defaultValue);

                if (!$item || $item->field !== $field) {
                    throw new NotFoundHttpException();
                }

                $facade->setDefaultValue($item);
            }
        }

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "number" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsNumber(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\NumberCommandTrait $command */
        $facade = $field->asNumber();

        if ($command->minimumValue > $command->maximumValue) {
            throw new BadRequestHttpException($this->translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if ($command->defaultValue < $command->minimumValue || $command->defaultValue > $command->maximumValue) {

                $message = $this->translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimumValue,
                    '%maximum%' => $command->maximumValue,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimumValue);
        $facade->setMaximumValue($command->maximumValue);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "string" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsString(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\StringCommandTrait $command */
        $facade = $field->asString($this->stringRepository);

        $pcre = $facade->getPCRE();

        $pcre->check   = $command->pcreCheck;
        $pcre->search  = $command->pcreSearch;
        $pcre->replace = $command->pcreReplace;

        if (mb_strlen($command->defaultValue) > $command->maximumLength) {

            $message = $this->translator->trans('field.error.default_value_length', [
                '%maximum%' => $command->maximumLength,
            ]);

            throw new BadRequestHttpException($message);
        }

        if (!$pcre->validate($command->defaultValue)) {
            throw new BadRequestHttpException($this->translator->trans('field.error.default_value_format'));
        }

        $facade->setMaximumLength($command->maximumLength);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }

    /**
     * Copies field-specific parameters from create/update command to specified "text" field.
     *
     * @param Command\AbstractFieldCommand $command
     * @param Field                        $field
     *
     * @return Field Updated field entity.
     */
    protected function copyAsText(Command\AbstractFieldCommand $command, Field $field): Field
    {
        /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\TextCommandTrait $command */
        $facade = $field->asText($this->textRepository);

        $pcre = $facade->getPCRE();

        $pcre->check   = $command->pcreCheck;
        $pcre->search  = $command->pcreSearch;
        $pcre->replace = $command->pcreReplace;

        if (mb_strlen($command->defaultValue) > $command->maximumLength) {

            $message = $this->translator->trans('field.error.default_value_length', [
                '%maximum%' => $command->maximumLength,
            ]);

            throw new BadRequestHttpException($message);
        }

        if (!$pcre->validate($command->defaultValue)) {
            throw new BadRequestHttpException($this->translator->trans('field.error.default_value_format'));
        }

        $facade->setMaximumLength($command->maximumLength);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }
}
