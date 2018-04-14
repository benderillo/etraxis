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
 * Date field trait.
 */
trait DateTrait
{
    /**
     * Returns this field as a field of a "date" type.
     *
     * @return DateInterface
     */
    public function asDate(): DateInterface
    {
        return new class($this->parameters) implements DateInterface {
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
            public function setMinimumValue(int $value): DateInterface
            {
                if ($value < DateInterface::MIN_VALUE) {
                    $value = DateInterface::MIN_VALUE;
                }

                if ($value > DateInterface::MAX_VALUE) {
                    $value = DateInterface::MAX_VALUE;
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
            public function setMaximumValue(int $value): DateInterface
            {
                if ($value < DateInterface::MIN_VALUE) {
                    $value = DateInterface::MIN_VALUE;
                }

                if ($value > DateInterface::MAX_VALUE) {
                    $value = DateInterface::MAX_VALUE;
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
            public function setDefaultValue(?int $value): DateInterface
            {
                if ($value !== null) {

                    if ($value < DateInterface::MIN_VALUE) {
                        $value = DateInterface::MIN_VALUE;
                    }

                    if ($value > DateInterface::MAX_VALUE) {
                        $value = DateInterface::MAX_VALUE;
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
