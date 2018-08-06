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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use Webinarium\PropertyTrait;

/**
 * Issue.
 *
 * @ORM\Table(
 *     name="issues",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"author_id", "created_at"})
 *     })
 * @ORM\Entity(repositoryClass="eTraxis\IssuesDomain\Model\Repository\IssueRepository")
 *
 * @property-read int          $id          Unique ID.
 * @property-read string       $fullId      Full unique ID with template prefix.
 * @property      string       $subject     Subject of the issue.
 * @property-read Project      $project     Issue project.
 * @property-read Template     $template    Issue template.
 * @property      State        $state       Current state.
 * @property-read User         $author      Author of the issue.
 * @property      null|User    $responsible Current responsible of the issue.
 * @property-read int          $createdAt   Unix Epoch timestamp when the issue has been created.
 * @property-read int          $changedAt   Unix Epoch timestamp when the issue has been changed last time.
 * @property-read null|int     $closedAt    Unix Epoch timestamp when the issue has been closed, if so.
 * @property-read null|int     $suspendedAt Unix Epoch timestamp when the issue should be resumed, if suspended.
 * @property-read bool         $isCritical  Whether the issue is critical (remains opened for too long).
 * @property-read bool         $isFrozen    Whether the issue is frozen (read-only).
 * @property-read bool         $isClosed    Whether the issue is closed.
 * @property-read bool         $isSuspended Whether the issue is suspended.
 * @property-read Event[]      $events      List of issue events.
 * @property-read FieldValue[] $values      List of field values.
 */
class Issue
{
    use PropertyTrait;

    // Constraints.
    public const MAX_SUBJECT = 250;

    // Utility constants.
    protected const SECS_IN_DAY = 86400;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=250)
     */
    protected $subject;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\TemplatesDomain\Model\Entity\State", fetch="EAGER")
     * @ORM\JoinColumn(name="state_id", nullable=false, referencedColumnName="id")
     */
    protected $state;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\SecurityDomain\Model\Entity\User")
     * @ORM\JoinColumn(name="author_id", nullable=false, referencedColumnName="id")
     */
    protected $author;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="eTraxis\SecurityDomain\Model\Entity\User")
     * @ORM\JoinColumn(name="responsible_id", referencedColumnName="id")
     */
    protected $responsible;

    /**
     * @var int
     *
     * @ORM\Column(name="created_at", type="integer")
     */
    protected $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(name="changed_at", type="integer")
     */
    protected $changedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="closed_at", type="integer", nullable=true)
     */
    protected $closedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="suspended_at", type="integer", nullable=true)
     */
    protected $suspendedAt;

    /**
     * @var ArrayCollection|Event[]
     *
     * @ORM\OneToMany(targetEntity="Event", mappedBy="issue")
     * @ORM\OrderBy({"createdAt": "ASC"})
     */
    protected $eventsCollection;

    /**
     * @var ArrayCollection|FieldValue[]
     *
     * @ORM\OneToMany(targetEntity="FieldValue", mappedBy="issue")
     */
    protected $valuesCollection;

    /**
     * Creates new issue.
     *
     * @param User $author
     */
    public function __construct(User $author)
    {
        $this->author = $author;

        $this->createdAt = $this->changedAt = time();

        $this->eventsCollection = new ArrayCollection();
        $this->valuesCollection = new ArrayCollection();
    }

    /**
     * Updates the timestamp of when the issue has been changed.
     */
    public function touch(): void
    {
        $this->changedAt = time();
    }

    /**
     * Suspends the issue until specified timestamp.
     *
     * @param int $timestamp Unix Epoch timestamp.
     */
    public function suspend(int $timestamp): void
    {
        $this->suspendedAt = $timestamp;
    }

    /**
     * Resumes the issue if suspended.
     */
    public function resume(): void
    {
        $this->suspendedAt = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getters(): array
    {
        return [

            'fullId' => function (): string {
                return sprintf('%s-%03d', $this->state->template->prefix, $this->id);
            },

            'project' => function (): Project {
                return $this->state->template->project;
            },

            'template' => function (): Template {
                return $this->state->template;
            },

            'isCritical' => function (): bool {

                if ($this->state->template->criticalAge !== null && $this->closedAt === null) {
                    $duration = ($this->closedAt ?? time()) - $this->createdAt;
                    $period   = ceil($duration / self::SECS_IN_DAY);

                    return $this->state->template->criticalAge < $period;
                }

                return false;
            },

            'isFrozen' => function (): bool {

                if ($this->state->template->frozenTime !== null && $this->closedAt !== null) {
                    $duration = time() - $this->closedAt;
                    $period   = ceil($duration / self::SECS_IN_DAY);

                    return $this->state->template->frozenTime < $period;
                }

                return false;
            },

            'isClosed' => function (): bool {
                return $this->closedAt !== null;
            },

            'isSuspended' => function (): bool {
                return $this->suspendedAt !== null && $this->suspendedAt > time();
            },

            'events' => function (): array {
                return $this->eventsCollection->getValues();
            },

            'values' => function (): array {
                return $this->valuesCollection->getValues();
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setters(): array
    {
        return [

            'state' => function (State $value): void {
                if ($this->state === null || $this->state->template === $value->template) {
                    $this->state    = $value;
                    $this->closedAt = $value->type === StateType::FINAL ? time() : null;
                }
                else {
                    throw new \UnexpectedValueException('Unknown state: ' . $value->name);
                }
            },
        ];
    }
}
