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

class FieldRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Field::class);
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
