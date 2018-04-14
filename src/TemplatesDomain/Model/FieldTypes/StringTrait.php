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
use eTraxis\TemplatesDomain\Model\Entity\FieldPCRE;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;

/**
 * String field trait.
 */
trait StringTrait
{
    /**
     * Returns this field as a field of a "string" type.
     *
     * @param StringValueRepository $repository
     *
     * @return StringInterface
     */
    public function asString(StringValueRepository $repository): StringInterface
    {
        return new class($repository, $this->pcre, $this->parameters) implements StringInterface {
            protected $repository;
            protected $pcre;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param StringValueRepository $repository
             * @param FieldPCRE             $pcre
             * @param FieldParameters       $parameters
             */
            public function __construct(StringValueRepository $repository, FieldPCRE $pcre, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->pcre       = $pcre;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumLength(int $length): StringInterface
            {
                if ($length < StringInterface::MIN_LENGTH) {
                    $length = StringInterface::MIN_LENGTH;
                }

                if ($length > StringInterface::MAX_LENGTH) {
                    $length = StringInterface::MAX_LENGTH;
                }

                $this->parameters->parameter1 = $length;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumLength(): int
            {
                return $this->parameters->parameter1;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?string $value): StringInterface
            {
                if (mb_strlen($value) > StringInterface::MAX_LENGTH) {
                    $value = mb_substr($value, 0, StringInterface::MAX_LENGTH);
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

                /** @var \eTraxis\TemplatesDomain\Model\Entity\StringValue $string */
                $string = $this->repository->find($this->parameters->defaultValue);

                return $string->value;
            }

            /**
             * {@inheritdoc}
             */
            public function getPCRE(): FieldPCRE
            {
                return $this->pcre;
            }
        };
    }
}
