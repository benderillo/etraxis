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

use eTraxis\SharedDomain\Framework\Validator\Constraints\DurationRange;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldParameters;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    protected function asDuration(): DurationInterface
    {
        return new class($this, $this->parameters) implements DurationInterface {
            protected $field;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param Field           $field
             * @param FieldParameters $parameters
             */
            public function __construct(Field $field, FieldParameters &$parameters)
            {
                $this->field      = $field;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function jsonSerialize()
            {
                return [
                    Field::JSON_MINIMUM => $this->getMinimumValue(),
                    Field::JSON_MAXIMUM => $this->getMaximumValue(),
                    Field::JSON_DEFAULT => $this->getDefaultValue(),
                ];
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                $message = $translator->trans('field.error.value_range', [
                    '%name%'    => $this->field->name,
                    '%minimum%' => $this->getMinimumValue(),
                    '%maximum%' => $this->getMaximumValue(),
                ]);

                $constraints = [
                    new Assert\Regex([
                        'pattern' => '/^\d{1,6}:[0-5]\d$/',
                    ]),
                    new DurationRange([
                        'min'        => $this->getMinimumValue(),
                        'max'        => $this->getMaximumValue(),
                        'minMessage' => $message,
                        'maxMessage' => $message,
                    ]),
                ];

                if ($this->field->isRequired) {
                    $constraints[] = new Assert\NotBlank();
                }

                return $constraints;
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
                return $this->toString($this->parameters->parameter1 ?? DurationInterface::MIN_VALUE);
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
                return $this->toString($this->parameters->parameter2 ?? DurationInterface::MAX_VALUE);
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
