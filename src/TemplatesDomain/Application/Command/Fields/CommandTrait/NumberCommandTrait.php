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
     */
    public $minimumValue;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $maximumValue;

    /**
     * @Assert\Range(min="-1000000000", max="1000000000")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $defaultValue;
}
