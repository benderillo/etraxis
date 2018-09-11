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
 * Trait for "number" field commands.
 *
 * @property int $minimumValue Minimum allowed value.
 * @property int $maximumValue Maximum allowed value.
 * @property int $defaultValue Default value of the field.
 */
trait NumberCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=-1000000000, maximum=1000000000, example=0, description="Minimum value.")
     */
    public $minimumValue;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=-1000000000, maximum=1000000000, example=100, description="Maximum value.")
     */
    public $maximumValue;

    /**
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     *
     * @Groups("api")
     * @API\Property(type="integer", minimum=-1000000000, maximum=1000000000, example=1, description="Default value.")
     */
    public $defaultValue;
}
