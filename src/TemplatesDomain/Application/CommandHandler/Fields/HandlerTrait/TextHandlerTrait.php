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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Fields\HandlerTrait;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\TextCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait TextHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::TEXT;
    }

    /**
     * {@inheritdoc}
     *
     * @param TextCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(TextCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\TextInterface $facade */
        $facade = $field->getFacade($manager);

        $pcre = $facade->getPCRE();

        $pcre->check   = $command->pcreCheck;
        $pcre->search  = $command->pcreSearch;
        $pcre->replace = $command->pcreReplace;

        if (mb_strlen($command->defaultValue) > $command->maximumLength) {

            $message = $translator->trans('field.error.default_value_length', [
                '%maximum%' => $command->maximumLength,
            ]);

            throw new BadRequestHttpException($message);
        }

        if (!$pcre->validate($command->defaultValue)) {
            throw new BadRequestHttpException($translator->trans('field.error.default_value_format'));
        }

        $facade->setMaximumLength($command->maximumLength);
        $facade->setDefaultValue($command->defaultValue);

        return $field;
    }
}
