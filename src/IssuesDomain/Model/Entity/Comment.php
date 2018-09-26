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
use Webinarium\PropertyTrait;

/**
 * Issue comment.
 *
 * @ORM\Table(
 *     name="comments",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"event_id"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\IssuesDomain\Model\Repository\CommentRepository")
 *
 * @property-read int    $id        Unique ID.
 * @property-read Issue  $issue     Issue of the comment.
 * @property-read Event  $event     Event which the comment has been posted by.
 * @property      string $body      Comment's body.
 * @property      bool   $isPrivate Whether the comment is private.
 */
class Comment implements \JsonSerializable
{
    use PropertyTrait;

    // Constraints.
    public const MAX_VALUE = 10000;

    // JSON properties.
    public const JSON_ID        = 'id';
    public const JSON_USER      = 'user';
    public const JSON_TIMESTAMP = 'timestamp';
    public const JSON_TEXT      = 'text';
    public const JSON_PRIVATE   = 'private';

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
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     */
    protected $body;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_private", type="boolean")
     */
    protected $isPrivate;

    /**
     * Creates comment.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
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
            self::JSON_TEXT      => $this->body,
            self::JSON_PRIVATE   => $this->isPrivate,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'issue' => function (): Issue {
                return $this->event->issue;
            },
        ];
    }
}
