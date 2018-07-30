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
use eTraxis\TemplatesDomain\Model\Entity\FieldPCRE;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Text field trait.
 */
trait TextTrait
{
    /**
     * Returns this field as a field of a "text" type.
     *
     * @param TextValueRepository $repository
     *
     * @return TextInterface
     */
    public function asText(TextValueRepository $repository): TextInterface
    {
        return new class($repository, $this, $this->pcre, $this->parameters) implements TextInterface {
            protected $repository;
            protected $field;
            protected $pcre;
            protected $parameters;

            /**
             * Passes original field's parameters as a reference so they can be modified inside the class.
             *
             * @param TextValueRepository $repository
             * @param Field               $field
             * @param FieldPCRE           $pcre
             * @param FieldParameters     $parameters
             */
            public function __construct(TextValueRepository $repository, Field $field, FieldPCRE $pcre, FieldParameters &$parameters)
            {
                $this->repository = $repository;
                $this->field      = $field;
                $this->pcre       = $pcre;
                $this->parameters = &$parameters;
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator): array
            {
                $constraints = [
                    new Assert\Length([
                        'max' => $this->getMaximumLength(),
                    ]),
                ];

                if ($this->field->isRequired) {
                    $constraints[] = new Assert\NotBlank();
                }

                if ($this->pcre->check) {
                    $constraints[] = new Assert\Regex([
                        'pattern' => sprintf('/^%s$/', $this->pcre->check),
                    ]);
                }

                return $constraints;
            }

            /**
             * {@inheritdoc}
             */
            public function setMaximumLength(int $length): TextInterface
            {
                if ($length < TextInterface::MIN_LENGTH) {
                    $length = TextInterface::MIN_LENGTH;
                }

                if ($length > TextInterface::MAX_LENGTH) {
                    $length = TextInterface::MAX_LENGTH;
                }

                $this->parameters->parameter1 = $length;

                return $this;
            }

            /**
             * {@inheritdoc}
             */
            public function getMaximumLength(): int
            {
                return $this->parameters->parameter1 ?? TextInterface::MAX_LENGTH;
            }

            /**
             * {@inheritdoc}
             */
            public function setDefaultValue(?string $value): TextInterface
            {
                if (mb_strlen($value) > TextInterface::MAX_LENGTH) {
                    $value = mb_substr($value, 0, TextInterface::MAX_LENGTH);
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

                /** @var \eTraxis\TemplatesDomain\Model\Entity\TextValue $text */
                $text = $this->repository->find($this->parameters->defaultValue);

                return $text->value;
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
