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
use eTraxis\IssuesDomain\Model\Dictionary\EventType;
use eTraxis\IssuesDomain\Model\Entity\Event;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class EventRepository extends ServiceEntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(Event $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * Finds all events of specified issue.
     *
     * @param Issue $issue
     * @param bool  $showPrivate
     *
     * @return Event[]
     */
    public function findAllByIssue(Issue $issue, bool $showPrivate): array
    {
        $query = $this->createQueryBuilder('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue);

        if (!$showPrivate) {
            $query->andWhere('event.type <> :private');
            $query->setParameter('private', EventType::PRIVATE_COMMENT);
        }

        return $query->getQuery()->getResult();
    }
}
