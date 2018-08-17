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
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Entity\LastRead;
use eTraxis\SecurityDomain\Model\Entity\User;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LastReadRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LastRead::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(LastRead $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * Marks specified issue as read by specified user.
     *
     * @param Issue $issue
     * @param User  $user
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function markAsRead(Issue $issue, User $user): void
    {
        /** @var null|LastRead $entity */
        $entity = $this->findOneBy([
            'issue' => $issue,
            'user'  => $user,
        ]);

        // If value doesn't exist yet, create it.
        if ($entity === null) {
            $entity = new LastRead($issue, $user);
        }
        else {
            $entity->touch();
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);
    }
}
