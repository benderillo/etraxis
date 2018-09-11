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

namespace eTraxis\TemplatesDomain\Application\Command\Fields\CommandTrait;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for "decimal" field commands.
 *
 * @property float $minimumValue DecimalValue ID.
 * @property float $maximumValue DecimalValue ID.
 * @property float $defaultValue DecimalValue ID.
 */
trait DecimalCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^(\-|\+)?\d{1,10}(\.\d{1,10})?$/")
     *
     * @Groups("api")
     * @API\Property(type="decimal", minimum="-9999999999.9999999999", maximum="9999999999.9999999999", example="3.1415", description="Minimum value.")
     */
    public $minimumValue;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^(\-|\+)?\d{1,10}(\.\d{1,10})?$/")
     *
     * @Groups("api")
     * @API\Property(type="decimal", minimum="-9999999999.9999999999", maximum="9999999999.9999999999", example="3.1415", description="Maximum value.")
     */
    public $maximumValue;

    /**
     * @Assert\Regex("/^(\-|\+)?\d{1,10}(\.\d{1,10})?$/")
     *
     * @Groups("api")
     * @API\Property(type="decimal", minimum="-9999999999.9999999999", maximum="9999999999.9999999999", example="3.1415", description="Default value.")
     */
    public $defaultValue;
}
