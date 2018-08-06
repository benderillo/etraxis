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

namespace eTraxis\IssuesDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use eTraxis\IssuesDomain\Model\Entity\Change;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\FieldValue;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IssueRepository extends ServiceEntityRepository
{
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RegistryInterface      $registry,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository
    )
    {
        parent::__construct($registry, Issue::class);

        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Issue $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Issue $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * Sets new subject of the specified issue.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param Issue  $issue   Issie whose subject is being set.
     * @param Event  $event   Event related to this change.
     * @param string $subject New subject.
     */
    public function setSubject(Issue $issue, Event $event, string $subject): void
    {
        if ($issue->subject !== $subject) {

            $oldValue = $this->stringRepository->get($issue->subject)->id;
            $newValue = $this->stringRepository->get($subject)->id;

            $change = new Change($event, null, $oldValue, $newValue);

            $issue->subject = $subject;

            $this->getEntityManager()->persist($change);
            $this->getEntityManager()->persist($issue);
        }
    }

    /**
     * Sets value of the specified field in the specified issue.
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param Issue      $issue Issie whose field is being set.
     * @param Event      $event Event related to this change.
     * @param Field      $field Field to set.
     * @param null|mixed $value Value to set.
     *
     * @return null|FieldValue In case of an error returns NULL.
     */
    public function setFieldValue(Issue $issue, Event $event, Field $field, $value): ?FieldValue
    {
        $newValue = null;

        if ($value !== null) {

            switch ($field->type) {

                case FieldType::DECIMAL:
                    $newValue = $this->decimalRepository->get($value)->id;
                    break;

                case FieldType::STRING:
                    $newValue = $this->stringRepository->get($value)->id;
                    break;

                case FieldType::TEXT:
                    $newValue = $this->textRepository->get($value)->id;
                    break;

                case FieldType::CHECKBOX:
                    $newValue = $value ? 1 : 0;
                    break;

                case FieldType::LIST:

                    $item = $this->listRepository->findOneByValue($field, $value);

                    if ($item === null) {
                        return null;
                    }

                    $newValue = $item->id;
                    break;

                case FieldType::ISSUE:

                    if ($this->find($value) === null) {
                        return null;
                    }

                    $newValue = $value;
                    break;

                case FieldType::DATE:
                    $timezone = timezone_open($event->user->timezone) ?? null;
                    $newValue = date_create_from_format('Y-m-d', $value, $timezone)->getTimestamp();
                    break;

                case FieldType::DURATION:
                    $newValue = $field->asDuration()->toNumber($value);
                    break;

                default:
                    $newValue = $value;
            }
        }

        /** @var null|FieldValue $fieldValue */
        $fieldValue = $this->getEntityManager()->getRepository(FieldValue::class)->findOneBy([
            'issue' => $issue,
            'field' => $field,
        ]);

        // If value doesn't exist yet, create it; otherwise register a change.
        if ($fieldValue === null) {
            $fieldValue = new FieldValue($issue, $field, $newValue);
        }
        elseif ($fieldValue->value !== $newValue) {
            $change = new Change($event, $field, $fieldValue->value, $newValue);
            $this->getEntityManager()->persist($change);

            $fieldValue->value = $newValue;
        }

        $this->getEntityManager()->persist($fieldValue);

        return $fieldValue;
    }
}
