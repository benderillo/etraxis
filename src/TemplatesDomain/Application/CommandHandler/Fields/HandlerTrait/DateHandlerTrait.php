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
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\DateCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait DateHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::DATE;
    }

    /**
     * {@inheritdoc}
     *
     * @param DateCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(DateCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\DateInterface $facade */
        $facade = $field->getFacade($manager);

        if ($command->minimum > $command->maximum) {
            throw new BadRequestHttpException($translator->trans('field.error.min_max_values'));
        }

        if ($command->default !== null) {

            if ($command->default < $command->minimum || $command->default > $command->maximum) {

                $message = $translator->trans('field.error.default_value_range', [
                    '%minimum%' => $command->minimum,
                    '%maximum%' => $command->maximum,
                ]);

                throw new BadRequestHttpException($message);
            }
        }

        $facade->setMinimumValue($command->minimum);
        $facade->setMaximumValue($command->maximum);
        $facade->setDefaultValue($command->default);

        return $field;
    }
}
