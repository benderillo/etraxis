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

use eTraxis\TemplatesDomain\Model\Entity\FieldParameters;

/**
 * Checkbox field trait.
 */
trait CheckboxTrait
{
    /**
     * Returns this field as a field of a "checkbox" type.
     *
     * @return CheckboxInterface
     */
    public function asCheckbox(): CheckboxInterface
    {
        return new class($this->parameters) implements CheckboxInterface {
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param FieldParameters $parameters
             */
            public function __construct(FieldParameters &$parameters)
            {
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(bool $value): CheckboxInterface
            {
                $this->parameters->defaultValue = $value ? 1 : 0;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): bool
            {
                return (bool) $this->parameters->defaultValue;
            }
        };
    }
}
