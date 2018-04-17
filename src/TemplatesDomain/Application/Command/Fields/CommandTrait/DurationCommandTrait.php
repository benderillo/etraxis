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
 * Trait for "duration" field commands.
 *
 * @property int $minimumValue Amount of minutes from 0:00 till 999999:59.
 * @property int $maximumValue Amount of minutes from 0:00 till 999999:59.
 * @property int $defaultValue Amount of minutes from 0:00 till 999999:59.
 */
trait DurationCommandTrait
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d{1,6}:[0-5][0-9]$/")
     */
    public $minimumValue;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d{1,6}:[0-5][0-9]$/")
     */
    public $maximumValue;

    /**
     * @Assert\Regex("/^\d{1,6}:[0-5][0-9]$/")
     */
    public $defaultValue;
}
