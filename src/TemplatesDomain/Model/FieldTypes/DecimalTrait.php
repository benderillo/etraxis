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

use eTraxis\SharedDomain\Framework\Validator\Constraints\DecimalRange;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\FieldParameters;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Decimal field trait.
 */
trait DecimalTrait
{
    /**
     * Returns this field as a field of a "decimal" type.
     *
     * @param DecimalValueRepository $repository
     *
     * @return DecimalInterface
     */
    public function asDecimal(DecimalValueRepository $repository): DecimalInterface
    {
        return new class($repository, $this, $this->parameters) implements DecimalInterface {
            protected $repository;
            protected $field;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param DecimalValueRepository $repository
             * @param Field                  $field
             * @param FieldParameters        $parameters
             */
            public function __construct(DecimalValueRepository $repository, Field $field, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->field      = $field;
                $this->parameters = &$parameters;
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
                        'pattern' => '/^(\-|\+)?\d{1,10}(\.\d{1,10})?$/',
                    ]),
                    new DecimalRange([
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
            public function setMinimumValue(string $value): DecimalInterface
            {
                if (bccomp($value, DecimalInterface::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                    $value = DecimalInterface::MIN_VALUE;
                }

                if (bccomp($value, DecimalInterface::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                    $value = DecimalInterface::MAX_VALUE;
                }

                $this->parameters->parameter1 = $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMinimumValue(): string
            {
                /** @var DecimalValue $decimal */
                $decimal = $this->repository->find($this->parameters->parameter1);

                return $decimal !== null ? $decimal->value : DecimalInterface::MIN_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumValue(string $value): DecimalInterface
            {
                if (bccomp($value, DecimalInterface::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                    $value = DecimalInterface::MIN_VALUE;
                }

                if (bccomp($value, DecimalInterface::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                    $value = DecimalInterface::MAX_VALUE;
                }

                $this->parameters->parameter2 = $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumValue(): string
            {
                /** @var DecimalValue $decimal */
                $decimal = $this->repository->find($this->parameters->parameter2);

                return $decimal !== null ? $decimal->value : DecimalInterface::MAX_VALUE;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?string $value): DecimalInterface
            {
                if ($value !== null) {

                    if (bccomp($value, DecimalInterface::MIN_VALUE, DecimalValue::PRECISION) < 0) {
                        $value = DecimalInterface::MIN_VALUE;
                    }

                    if (bccomp($value, DecimalInterface::MAX_VALUE, DecimalValue::PRECISION) > 0) {
                        $value = DecimalInterface::MAX_VALUE;
                    }
                }

                $this->parameters->defaultValue = ($value === null)
                    ? null
                    : $this->repository->get($value)->id;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getDefaultValue(): ?string
            {
                if ($this->parameters->defaultValue === null) {
                    return null;
                }

                /** @var DecimalValue $decimal */
                $decimal = $this->repository->find($this->parameters->defaultValue);

                return $decimal->value;
            }
        };
    }
}
