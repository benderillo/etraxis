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

namespace eTraxis\IssuesDomain\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Webinarium\PropertyTrait;

/**
 * Issue field change.
 *
 * @ORM\Table(
 *     name="changes",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"event_id", "field_id"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\IssuesDomain\Model\Repository\ChangeRepository")
 *
 * @property-read int        $id       Unique ID.
 * @property-read Event      $event    Changing event.
 * @property-read null|Field $field    Changed field (NULL for issue subject).
 * @property-read null|int   $oldValue Old value of the field (see "FieldValue::$value" for details).
 * @property-read null|int   $newValue New value of the field (see "FieldValue::$value" for details).
 */
class Change implements \JsonSerializable
{
    use PropertyTrait;

    // JSON properties.
    public const JSON_ID        = 'id';
    public const JSON_USER      = 'user';
    public const JSON_TIMESTAMP = 'timestamp';
    public const JSON_FIELD     = 'field';
    public const JSON_OLD_VALUE = 'old_value';
    public const JSON_NEW_VALUE = 'new_value';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event")
     * @ORM\JoinColumn(name="event_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $event;

    /**
     * @var Field
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\TemplatesDomain\Model\Entity\Field")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @var int
     *
     * @ORM\Column(name="old_value", type="integer", nullable=true)
     */
    protected $oldValue;

    /**
     * @var int
     *
     * @ORM\Column(name="new_value", type="integer", nullable=true)
     */
    protected $newValue;

    /**
     * Creates new change.
     *
     * @param Event      $event
     * @param null|Field $field
     * @param null|int   $oldValue
     * @param null|int   $newValue
     */
    public function __construct(Event $event, ?Field $field, ?int $oldValue, ?int $newValue)
    {
        $this->event    = $event;
        $this->field    = $field;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            self::JSON_ID        => $this->id,
            self::JSON_USER      => [
                User::JSON_ID       => $this->event->user->id,
                User::JSON_EMAIL    => $this->event->user->email,
                User::JSON_FULLNAME => $this->event->user->fullname,
            ],
            self::JSON_TIMESTAMP => $this->event->createdAt,
            self::JSON_FIELD     => $this->field === null ? null : [
                Field::JSON_ID          => $this->field->id,
                Field::JSON_NAME        => $this->field->name,
                Field::JSON_TYPE        => $this->field->type,
                Field::JSON_DESCRIPTION => $this->field->description,
                Field::JSON_POSITION    => $this->field->position,
                Field::JSON_REQUIRED    => $this->field->isRequired,
            ],
            self::JSON_OLD_VALUE => $this->oldValue,
            self::JSON_NEW_VALUE => $this->newValue,
        ];
    }
}
