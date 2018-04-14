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
 * Duration field trait.
 */
trait DurationTrait
{
    /**
     * Returns this field as a field of a "duration" type.
     *
     * @return DurationInterface
     */
    public function asDuration(): DurationInterface
    {
        return new class($this->parameters) implements DurationInterface {
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
            public function setMinimumValue(string $value): DurationInterface
            {
                $this->parameters->parameter1 = $this->toNumber($value);

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMinimumValue(): string
            {
                return $this->toString($this->parameters->parameter1);
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumValue(string $value): DurationInterface
            {
                $this->parameters->parameter2 = $this->toNumber($value);

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumValue(): string
            {
                return $this->toString($this->parameters->parameter2);
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?string $value): DurationInterface
            {
                $this->parameters->defaultValue = $this->toNumber($value);

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?string
            {
                return $this->toString($this->parameters->defaultValue);
            }

            /**
             * {@inheritdoc}
             */
            public function toNumber(?string $value): ?int
            {
                if ($value === null) {
                    return null;
                }

                if (!preg_match('/^\d{1,6}:[0-5][0-9]$/', $value)) {
                    return null;
                }

                [$hh, $mm] = explode(':', $value);

                return $hh * 60 + $mm;
            }

            /**
             * {@inheritdoc}
             */
            public function toString(?int $value): ?string
            {
                if ($value === null) {
                    return null;
                }

                if ($value < DurationInterface::MIN_VALUE) {
                    $value = DurationInterface::MIN_VALUE;
                }

                if ($value > DurationInterface::MAX_VALUE) {
                    $value = DurationInterface::MAX_VALUE;
                }

                return intdiv($value, 60) . ':' . str_pad($value % 60, 2, '0', STR_PAD_LEFT);
            }
        };
    }
}
