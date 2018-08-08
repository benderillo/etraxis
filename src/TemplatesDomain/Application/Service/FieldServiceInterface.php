<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  You should have received a copy of the GNU General Public License
//  along with the file. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\TemplatesDomain\Application\Service;

use eTraxis\TemplatesDomain\Application\Command\Fields\AbstractFieldCommand;
use eTraxis\TemplatesDomain\Model\Entity\Field;

/**
 * Service to process fields of any type.
 */
interface FieldServiceInterface
{
    /**
     * Returns list of constraints for field value validation.
     *
     * @param Field    $field     Field which value has to be validated.
     * @param null|int $timestamp Timestamp when current value of the field has been created, if applicable.
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    public function getValidationConstraints(Field $field, ?int $timestamp = null): array;

    /**
     * Copies field-specific parameters from create/update command to specified field.
     *
     * @param AbstractFieldCommand $command
     * @param Field                $field
     *
     * @return Field Updated field entity.
     */
    public function copyCommandToField(AbstractFieldCommand $command, Field $field): Field;
}
