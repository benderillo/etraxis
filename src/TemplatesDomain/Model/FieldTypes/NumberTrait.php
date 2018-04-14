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
 * Number field trait.
 */
trait NumberTrait
{
    /**
     * Returns this field as a field of a "number" type.
     *
     * @return NumberInterface
     */
    public function asNumber(): NumberInterface
    {
        return new class($this->parameters) implements NumberInterface {
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
            public function setMinimumValue(int $value): NumberInterface
            {
                if ($value < NumberInterface::MIN_VALUE) {
                    $value = NumberInterface::MIN_VALUE;
                }

                if ($value > NumberInterface::MAX_VALUE) {
                    $value = NumberInterface::MAX_VALUE;
                }

                $this->parameters->parameter1 = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMinimumValue(): int
            {
                return $this->parameters->parameter1;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumValue(int $value): NumberInterface
            {
                if ($value < NumberInterface::MIN_VALUE) {
                    $value = NumberInterface::MIN_VALUE;
                }

                if ($value > NumberInterface::MAX_VALUE) {
                    $value = NumberInterface::MAX_VALUE;
                }

                $this->parameters->parameter2 = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumValue(): int
            {
                return $this->parameters->parameter2;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?int $value): NumberInterface
            {
                if ($value !== null) {

                    if ($value < NumberInterface::MIN_VALUE) {
                        $value = NumberInterface::MIN_VALUE;
                    }

                    if ($value > NumberInterface::MAX_VALUE) {
                        $value = NumberInterface::MAX_VALUE;
                    }
                }

                $this->parameters->defaultValue = $value;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?int
            {
                return $this->parameters->defaultValue;
            }
        };
    }
}
