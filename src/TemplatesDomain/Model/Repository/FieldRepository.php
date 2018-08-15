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

namespace eTraxis\TemplatesDomain\Model\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldRepository extends ServiceEntityRepository
{
    protected $translator;
    protected $decimalRepository;
    protected $stringRepository;
    protected $textRepository;
    protected $listRepository;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RegistryInterface      $registry,
        TranslatorInterface    $translator,
        DecimalValueRepository $decimalRepository,
        StringValueRepository  $stringRepository,
        TextValueRepository    $textRepository,
        ListItemRepository     $listRepository
    )
    {
        parent::__construct($registry, Field::class);

        $this->translator        = $translator;
        $this->decimalRepository = $decimalRepository;
        $this->stringRepository  = $stringRepository;
        $this->textRepository    = $textRepository;
        $this->listRepository    = $listRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Field $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Field $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * Returns list of constraints for field value validation.
     *
     * @param Field    $field     Field which value has to be validated.
     * @param null|int $timestamp Timestamp when current value of the field has been created, if applicable.
     *
     * @return \Symfony\Component\Validator\Constraint[]
     */
    public function getValidationConstraints(Field $field, ?int $timestamp = null): array
    {
        switch ($field->type) {

            case FieldType::CHECKBOX:
                return $field->asCheckbox()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::DATE:
                return $field->asDate()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::DECIMAL:
                return $field->asDecimal($this->decimalRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::DURATION:
                return $field->asDuration()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::ISSUE:
                return $field->asIssue()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::LIST:
                return $field->asList($this->listRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::NUMBER:
                return $field->asNumber()->getValidationConstraints($this->translator, $timestamp);

            case FieldType::STRING:
                return $field->asString($this->stringRepository)->getValidationConstraints($this->translator, $timestamp);

            case FieldType::TEXT:
                return $field->asText($this->textRepository)->getValidationConstraints($this->translator, $timestamp);
        }

        return [];
    }
}
