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
use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractUpdateFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait\ListCommandTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extension for "Create/update field" command handlers.
 */
trait ListHandlerTrait
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldType(): string
    {
        return FieldType::LIST;
    }

    /**
     * {@inheritdoc}
     *
     * @param ListCommandTrait $command
     */
    protected function copyCommandToField(TranslatorInterface $translator, EntityManagerInterface $manager, AbstractFieldCommand $command, Field $field): Field
    {
        if (!in_array(ListCommandTrait::class, class_uses($command), true)) {
            throw new \UnexpectedValueException('Unsupported command.');
        }

        if ($field->type !== $this->getSupportedFieldType()) {
            throw new \UnexpectedValueException('Unsupported field type.');
        }

        /** @var \eTraxis\TemplatesDomain\Model\FieldTypes\ListInterface $facade */
        $facade = $field->getFacade($manager);

        if (get_parent_class($command) === AbstractUpdateFieldCommand::class) {

            /** @var \eTraxis\TemplatesDomain\Application\Command\Fields\UpdateListFieldCommand $command */
            if ($command->defaultValue === null) {
                $facade->setDefaultValue(null);
            }
            else {
                /** @var null|\eTraxis\TemplatesDomain\Model\Entity\ListItem $item */
                $item = $manager->getRepository(ListItem::class)->find($command->defaultValue);

                if (!$item || $item->field !== $field) {
                    throw new NotFoundHttpException();
                }

                $facade->setDefaultValue($item);
            }
        }

        return $field;
    }
}
