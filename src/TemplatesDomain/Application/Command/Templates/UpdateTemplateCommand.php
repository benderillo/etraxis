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

namespace eTraxis\TemplatesDomain\Application\Command\Templates;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified template.
 *
 * @property int    $id          Template ID.
 * @property string $name        New name.
 * @property string $prefix      New prefix.
 * @property string $description New description.
 * @property int    $criticalAge New critical age.
 * @property int    $frozenTime  New frozen time.
 */
class UpdateTemplateCommand
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
     * @Assert\NotBlank
     * @Assert\Length(max="5")
     */
    public $prefix;

    /**
     * @Assert\Length(max="100")
     */
    public $description;

    /**
     * @Assert\Range(min="1", max="100")
     */
    public $criticalAge;

    /**
     * @Assert\Range(min="1", max="100")
     */
    public $frozenTime;
}
