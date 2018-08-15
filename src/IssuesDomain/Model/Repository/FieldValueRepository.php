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
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use eTraxis\TemplatesDomain\Model\Repository\StringValueRepository;
use eTraxis\TemplatesDomain\Model\Repository\TextValueRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class FieldValueRepository extends ServiceEntityRepository
{
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;
    protected $issueRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RegistryInterface      $registry,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository,
        IssueRepository        $issueRepository
    )
    {
        parent::__construct($registry, FieldValue::class);

        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
        $this->issueRepository   = $issueRepository;
    }

    /**
     * Returns human-readable version of the specified field value.
     *
     * @param FieldValue $fieldValue Field value.
     * @param User       $user       Current user.
     *
     * @return null|mixed Human-readable value.
     */
    public function getFieldValue(FieldValue $fieldValue, User $user)
    {
        if ($fieldValue->value !== null) {

            switch ($fieldValue->field->type) {

                case FieldType::CHECKBOX:

                    return $fieldValue->value ? true : false;

                case FieldType::DATE:

                    $date = date_create(null, timezone_open($user->timezone) ?: null);
                    $date->setTimestamp($fieldValue->value);

                    return $date->format('Y-m-d');

                case FieldType::DECIMAL:

                    /** @var \eTraxis\TemplatesDomain\Model\Entity\DecimalValue $value */
                    $value = $this->decimalRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;

                case FieldType::DURATION:

                    return $fieldValue->field->asDuration()->toString($fieldValue->value);

                case FieldType::ISSUE:

                    return $fieldValue->value;

                case FieldType::LIST:

                    /** @var \eTraxis\TemplatesDomain\Model\Entity\ListItem $value */
                    $value = $this->listRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;

                case FieldType::NUMBER:

                    return $fieldValue->value;

                case FieldType::STRING:

                    /** @var \eTraxis\TemplatesDomain\Model\Entity\StringValue $value */
                    $value = $this->stringRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;

                case FieldType::TEXT:

                    /** @var \eTraxis\TemplatesDomain\Model\Entity\TextValue $value */
                    $value = $this->textRepository->find($fieldValue->value);

                    return $value === null ? null : $value->value;
            }
        }

        return null;
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

                case FieldType::CHECKBOX:
                    $newValue = $value ? 1 : 0;
                    break;

                case FieldType::DATE:
                    $timezone = timezone_open($event->user->timezone) ?? null;
                    $newValue = date_create_from_format('Y-m-d', $value, $timezone)->getTimestamp();
                    break;

                case FieldType::DECIMAL:
                    $newValue = $this->decimalRepository->get($value)->id;
                    break;

                case FieldType::DURATION:
                    $newValue = $field->asDuration()->toNumber($value);
                    break;

                case FieldType::ISSUE:

                    if ($this->issueRepository->find($value) === null) {
                        return null;
                    }

                    $newValue = $value;
                    break;

                case FieldType::LIST:

                    $item = $this->listRepository->findOneByValue($field, $value);

                    if ($item === null) {
                        return null;
                    }

                    $newValue = $item->id;
                    break;

                case FieldType::STRING:
                    $newValue = $this->stringRepository->get($value)->id;
                    break;

                case FieldType::TEXT:
                    $newValue = $this->textRepository->get($value)->id;
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
            $issue->touch();
        }
        elseif ($fieldValue->value !== $newValue) {
            $change = new Change($event, $field, $fieldValue->value, $newValue);
            $this->getEntityManager()->persist($change);

            $fieldValue->value = $newValue;
            $issue->touch();
        }

        $this->getEntityManager()->persist($fieldValue);

        return $fieldValue;
    }
}
