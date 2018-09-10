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
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $entity = parent::find($id, $lockMode, $lockVersion);

        if ($entity !== null) {
            $this->warmupCache([$entity]);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $entities = parent::findAll();

        $this->warmupCache($entities);

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $entities = parent::findBy($criteria, $orderBy, $limit, $offset);

        $this->warmupCache($entities);

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $entity = parent::findOneBy($criteria, $orderBy);

        if ($entity !== null) {
            $this->warmupCache([$entity]);
        }

        return $entity;
    }

    /**
     * Warms up the values cache for specified fields.
     *
     * @param Field[] $fields
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function warmupCache(array $fields): void
    {
        $decimalValues = [];
        $stringValues  = [];
        $textValues    = [];
        $listItems     = [];

        foreach ($fields as $field) {

            switch ($field->type) {

                case FieldType::DECIMAL:
                    $decimalValues[] = $field->parameters->parameter1;
                    $decimalValues[] = $field->parameters->parameter2;
                    $decimalValues[] = $field->parameters->defaultValue;
                    break;

                case FieldType::STRING:
                    $stringValues[] = $field->parameters->defaultValue;
                    break;

                case FieldType::TEXT:
                    $textValues[] = $field->parameters->defaultValue;
                    break;

                case FieldType::LIST:
                    $listItems[] = $field->parameters->defaultValue;
                    break;
            }
        }

        $this->decimalRepository->warmup($decimalValues);
        $this->stringRepository->warmup($stringValues);
        $this->textRepository->warmup($textValues);
        $this->listRepository->warmup($listItems);
    }
}
