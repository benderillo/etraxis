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
use eTraxis\IssuesDomain\Model\Entity\Watcher;
use Symfony\Bridge\Doctrine\RegistryInterface;

class WatcherRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Watcher::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Watcher $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }
}
