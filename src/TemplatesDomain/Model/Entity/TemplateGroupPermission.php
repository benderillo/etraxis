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

namespace eTraxis\TemplatesDomain\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use Webinarium\PropertyTrait;

/**
 * Template permission for group.
 *
 * @ORM\Table(
 *     name="template_group_permissions",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"template_id", "group_id", "permission"})
 *     })
 * @ORM\Entity
 *
 * @property-read Template $template   Template.
 * @property-read Group    $group      Group.
 * @property-read string   $permission Permission granted to the group for this template.
 */
class TemplateGroupPermission
{
    use PropertyTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Template
     *
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="groupPermissionsCollection")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\SecurityDomain\Model\Entity\Group")
     * @ORM\JoinColumn(name="group_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $group;

    /**
     * @var string
     *
     * @ORM\Column(name="permission", type="string", length=20)
     */
    protected $permission;

    /**
     * Constructor.
     *
     * @param Template $template
     * @param Group    $group
     * @param string   $permission
     */
    public function __construct(Template $template, Group $group, string $permission)
    {
        $this->template = $template;

        if ($group->isGlobal || $group->project === $template->project) {
            $this->group = $group;
        }

        if (TemplatePermission::has($permission)) {
            $this->permission = $permission;
        }
    }
}
