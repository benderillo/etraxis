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

namespace eTraxis\IssuesDomain\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates specified issue.
 *
 * @property int    $issue   Issue ID.
 * @property string $subject Issue subject.
 * @property array  $fields  Fields values (keys are field IDs).
 */
class UpdateIssueCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotBlank
     * @Assert\Regex("/^\d+$/")
     */
    public $issue;

    /**
     * @Assert\Length(max="250")
     */
    public $subject;

    /**
     * All the constraints are configured at run-time.
     */
    public $fields = [];
}
