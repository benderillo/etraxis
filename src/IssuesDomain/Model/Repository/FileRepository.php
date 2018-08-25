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
}
