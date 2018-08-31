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

namespace eTraxis\SecurityDomain\Application\Command\Users;

use Swagger\Annotations as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Enables specified accounts.
 *
 * @property int[] $users User IDs.
 */
class EnableUsersCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Type(type="array")
     * @Assert\Count(min="1", max="100")
     * @Assert\All({
     *     @Assert\NotBlank,
     *     @Assert\Regex("/^\d+$/")
     * })
     *
     * @Groups("api")
     * @API\Property(type="array", example={123, 456}, description="List of user IDs.",
     *     @API\Items(type="integer")
     * )
     */
    public $users;
}
