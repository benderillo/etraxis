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

namespace eTraxis\TemplatesDomain\Model\FieldTypes;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldParameters;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;

/**
 * List field trait.
 */
trait ListTrait
{
    /**
     * Returns this field as a field of a "list" type.
     *
     * @param ListItemRepository $repository
     *
     * @return ListInterface
     */
    public function asList(ListItemRepository $repository): ListInterface
    {
        return new class($repository, $this, $this->parameters) implements ListInterface {
            protected $repository;
            protected $field;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param ListItemRepository $repository
             * @param Field              $field
             * @param FieldParameters    $parameters
             */
            public function __construct(ListItemRepository $repository, Field $field, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->field      = $field;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?ListItem $value): ListInterface
            {
                if ($value === null) {
                    $this->parameters->defaultValue = null;
                }
                elseif ($value->field->id === $this->field->id) {
                    $this->parameters->defaultValue = $value->value;
                }

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?ListItem
            {
                if ($this->parameters->defaultValue === null) {
                    return null;
                }

                /** @var ListItem $item */
                $item = $this->repository->findOneBy([
                    'field' => $this->field,
                    'value' => $this->parameters->defaultValue,
                ]);

                return $item;
            }
        };
    }
}
