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
}
