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

use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\FieldParameters;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;

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
        return new class($repository, $this->parameters) implements DecimalInterface {
            protected $repository;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param DecimalValueRepository $repository
             * @param FieldParameters        $parameters
             */
            public function __construct(DecimalValueRepository $repository, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->parameters = &$parameters;
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

                return $decimal->value;
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

                return $decimal->value;
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
