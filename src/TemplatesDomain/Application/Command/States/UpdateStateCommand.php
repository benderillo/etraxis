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

namespace eTraxis\TemplatesDomain\Application\Command\States;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified state.
 *
 * @property int    $id          State ID.
 * @property string $name        New state name.
 * @property int    $responsible New type of responsibility management.
 * @property int    $nextState   ID of the state which is next by default.
 */
class UpdateStateCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $id;

    /**
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     */
    public $name;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible", "keys"}, strict=true)
     */
    public $responsible;

    /**
     * @Assert\Regex("/^\d+$/")
     */
    public $nextState;
}
