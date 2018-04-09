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

namespace eTraxis\SecurityDomain\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * Group.
 *
 * @ORM\Table(
 *     name="groups",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"project_id", "name"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\SecurityDomain\Model\Repository\GroupRepository")
 * @Assert\UniqueEntity(fields={"project", "name"}, message="group.conflict.name", ignoreNull=false)
 *
 * @property-read int     $id          Unique ID.
 * @property-read Project $project     Project of the group (NULL if the group is global).
 * @property      string  $name        Name of the group.
 * @property      string  $description Optional description of the group.
 * @property-read bool    $isGlobal    Whether the group is a global one.
 */
class Group
{
    use PropertyTrait;

    // Constraints.
    public const MAX_NAME        = 25;
    public const MAX_DESCRIPTION = 100;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\TemplatesDomain\Model\Entity\Project", inversedBy="groupsCollection")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $project;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=25)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100, nullable=true)
     */
    protected $description;

    /**
     * Creates new group in the specified project (NULL creates a global group).
     *
     * @param null|Project $project
     */
    public function __construct(?Project $project = null)
    {
        $this->project = $project;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'isGlobal' => function (): bool {
                return $this->project === null;
            },
        ];
    }
}
