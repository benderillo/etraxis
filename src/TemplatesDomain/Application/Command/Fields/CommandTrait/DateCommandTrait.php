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
 * Trait for "date" field commands.
 *
 * @property int $minimumValue Amount of days since current date (negative value shifts to the past).
 * @property int $maximumValue Amount of days since current date (negative value shifts to the past).
 * @property int $defaultValue Amount of days since current date (negative value shifts to the past).
 */
trait DateCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-2147483648", max="2147483647")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $minimumValue;

    /**
     * @Assert\NotBlank
     * @Assert\Range(min="-2147483648", max="2147483647")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $maximumValue;

    /**
     * @Assert\Range(min="-2147483648", max="2147483647")
     * @Assert\Regex("/^(\-|\+)?\d+$/")
     */
    public $defaultValue;
}
