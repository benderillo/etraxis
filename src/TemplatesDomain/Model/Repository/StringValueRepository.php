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
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StringValueRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StringValue::class);
    }

    /**
     * Finds specified string value entity.
     * If the value doesn't exist yet, creates it.
     *
     * @param string $value String value.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return StringValue
     */
    public function get(string $value): StringValue
    {
        /** @var StringValue $entity */
        $entity = $this->findOneBy([
            'token' => md5($value),
        ]);

        // If value doesn't exist yet, create it.
        if ($entity === null) {

            $entity = new StringValue($value);

            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush($entity);
        }

        return $entity;
    }
}
