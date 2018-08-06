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
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Issue field trait.
 */
trait IssueTrait
{
    /**
     * Returns this field as a field of a "issue" type.
     *
     * @return IssueInterface
     */
    public function asIssue(): IssueInterface
    {
        return new class($this) implements IssueInterface {
            protected $field;

            /**
             * Dependency Injection constructor.
             *
             * @param Field $field
             */
            public function __construct(Field $field)
            {
                $this->field = $field;
            }

            /**
             * {@inheritdoc}
             */
            public function getValidationConstraints(TranslatorInterface $translator, ?int $timestamp = null): array
            {
                $constraints = [
                    new Assert\Regex([
                        'pattern' => '/^\d+$/',
                    ]),
                    new Assert\GreaterThan([
                        'value' => 0,
                    ]),
                ];

                if ($this->field->isRequired) {
                    $constraints[] = new Assert\NotBlank();
                }

                return $constraints;
            }
        };
    }
}
