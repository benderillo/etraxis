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

/** @noinspection PhpUnusedPrivateMethodInspection */

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Fields\HandlerTrait;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DecimalCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait DecimalHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::DECIMAL;
    }

    /**
     * {@inheritdoc}
     *
     * @param DecimalCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(DecimalCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DecimalInterface $facade */
        $facade = $field->getFacade($manager);

        if (bccomp($command->minimumValue, $command->maximumValue, DecimalValue::PRECISION) > 0) {
            throw new BadRequestHttpException($translator->trans('field.error.min_max_values'));
        }

        if ($command->defaultValue !== null) {

            if (bccomp($command->defaultValue, $command->minimumValue, DecimalValue::PRECISION) < 0 ||
                bccomp($command->defaultValue, $command->maximumValue, DecimalValue::PRECISION) > 0)
            {

                $message = $translator->trans('field.error.default_value_range', [
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
}
