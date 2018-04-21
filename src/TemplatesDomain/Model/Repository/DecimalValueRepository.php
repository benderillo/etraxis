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
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DecimalValueRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DecimalValue::class);
    }

    /**
     * Finds specified decimal value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value Decimal value.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return DecimalValue
     */
    public function get(string $value): DecimalValue
    {
        /** @var null|DecimalValue $entity */
        $entity = $this->findOneBy([
            'value' => $value,
        ]);

        // If value doesn't exist yet, create it.
        if ($entity === null) {

            $entity = new DecimalValue($value);

            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush($entity);
        }

        return $entity;
    }
}
