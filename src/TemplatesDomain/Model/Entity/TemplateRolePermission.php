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
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use Webinarium\PropertyTrait;

/**
 * Template permission for system role.
 *
 * @ORM\Table(
 *     name="template_role_permissions",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"template_id", "role", "permission"})
 *     })
 * @ORM\Entity
 *
 * @property-read Template $template   Template.
 * @property-read string   $role       System role.
 * @property-read string   $permission Permission granted to the role for this template.
 */
class TemplateRolePermission
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
     * @ORM\ManyToOne(targetEntity="Template", inversedBy="rolePermissionsCollection")
     * @ORM\JoinColumn(name="template_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=20)
     */
    protected $role;

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
     * @param string   $role
     * @param string   $permission
     */
    public function __construct(Template $template, string $role, string $permission)
    {
        $this->template = $template;

        if (SystemRole::has($role)) {
            $this->role = $role;
        }

        if (TemplatePermission::has($permission)) {
            $this->permission = $permission;
        }
    }
}
