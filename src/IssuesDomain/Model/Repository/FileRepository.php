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
use eTraxis\IssuesDomain\Model\Entity\File;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class FileRepository extends ServiceEntityRepository
{
    /** @var string Path to files storage directory. */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    public function __construct(RegistryInterface $registry, string $storage)
    {
        parent::__construct($registry, File::class);

        $this->storage = realpath($storage) ?: $storage;
    }

    /**
     * @codeCoverageIgnore Proxy method.
     *
     * {@inheritdoc}
     */
    public function persist(File $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * Returns absolute path including filename to the specified attachment.
     *
     * @param File $entity
     *
     * @return null|string
     */
    public function getFullPath(File $entity): ?string
    {
        return $this->storage . \DIRECTORY_SEPARATOR . $entity->uuid;
    }

    /**
     * Finds all files of specified issue.
     *
     * @param Issue $issue
     * @param bool  $showRemoved
     *
     * @return File[]
     */
    public function findAllByIssue(Issue $issue, bool $showRemoved = false): array
    {
        $query = $this->createQueryBuilder('file')
            ->innerJoin('file.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue);

        if (!$showRemoved) {
            $query->andWhere('file.removedAt IS NULL');
        }

        return $query->getQuery()->getResult();
    }
}
