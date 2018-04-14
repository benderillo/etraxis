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
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\FieldTypes;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Webinarium\PropertyTrait;

/**
 * Field.
 *
 * @ORM\Table(
 *     name="fields",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"state_id", "name", "removed_at"}),
 *         @ORM\UniqueConstraint(columns={"state_id", "position", "removed_at"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\TemplatesDomain\Model\Repository\FieldRepository")
 * @Assert\UniqueEntity(fields={"state", "name", "removedAt"}, message="field.conflict.name", ignoreNull=false)
 *
 * @property-read int    $id          Unique ID.
 * @property-read State  $state       State of the field.
 * @property      string $name        Name of the field.
 * @property-read string $type        Type of the field (see the "FieldType" dictionary).
 * @property      string $description Optional description of the field.
 * @property      int    $position    Ordinal number of the field. No duplicates of this number among fields of the same state are allowed.
 * @property      int    $removedAt   Unix Epoch timestamp when the field has been removed (NULL while field is present).
 * @property      bool   $isRequired  Whether the field is required.
 */
class Field
{
    use PropertyTrait;

    use FieldTypes\NumberTrait;
    use FieldTypes\DecimalTrait;
    use FieldTypes\StringTrait;
    use FieldTypes\TextTrait;
    use FieldTypes\CheckboxTrait;
    use FieldTypes\ListTrait;
    use FieldTypes\IssueTrait;
    use FieldTypes\DateTrait;
    use FieldTypes\DurationTrait;

    // Constraints.
    public const MAX_NAME        = 50;
    public const MAX_DESCRIPTION = 1000;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="State", inversedBy="fieldsCollection", fetch="EAGER")
     * @ORM\JoinColumn(name="state_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    protected $position;

    /**
     * @var int
     *
     * @ORM\Column(name="removed_at", type="integer", nullable=true)
     */
    protected $removedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_required", type="boolean")
     */
    protected $isRequired;

    /**
     * @var FieldPCRE Perl-compatible regular expression options.
     *
     * @ORM\Embedded(class="FieldPCRE")
     */
    protected $pcre;

    /**
     * @var FieldParameters Field type-specific parameters.
     *
     * @ORM\Embedded(class="FieldParameters", columnPrefix=false)
     */
    protected $parameters;

    /**
     * Creates new field for the specified state.
     *
     * @param State  $state
     * @param string $type
     */
    public function __construct(State $state, string $type)
    {
        if (!FieldType::has($type)) {
            throw new \UnexpectedValueException('Unknown field type: ' . $type);
        }

        $this->state = $state;
        $this->type  = $type;

        $this->pcre       = new FieldPCRE();
        $this->parameters = new FieldParameters();
    }
}
