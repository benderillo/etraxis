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
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\CheckboxCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait CheckboxHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::CHECKBOX;
    }

    /**
     * {@inheritdoc}
     *
     * @param CheckboxCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(CheckboxCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\CheckboxInterface $facade */
        $facade = $field->getFacade($manager);

        $facade->setDefaultValue($command->default);

        return $field;
    }
}
