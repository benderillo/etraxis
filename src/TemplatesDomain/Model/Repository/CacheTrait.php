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

use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Cache\Simple\ArrayCache;

/**
 * PSR-16 cache to store found entities.
 */
trait CacheTrait
{
    /** @var \Psr\SimpleCache\CacheInterface */
    protected $cache;

    /**
     * Initialises the cache.
     * Must be called first before a call to any other function of this trait.
     */
    protected function createCache(): void
    {
        $this->cache = new ArrayCache(0, false);
    }

    /**
     * Tries to find an entity by its ID in the following sequence - cache, repository.
     * If the entity was retrieved from the repository, stores it in the cache.
     *
     * @param null|int $id
     * @param callable $callback
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return null|object
     */
    protected function findInCache(?int $id, callable $callback)
    {
        if ($id === null) {
            return null;
        }

        if ($this->cache->has("{$id}")) {
            return $this->cache->get("{$id}");
        }

        $entity = $callback($id);

        if ($entity !== null) {
            $this->cache->set("{$id}", $entity);
        }

        return $entity;
    }

    /**
     * Deletes specified entity from cache.
     *
     * @param null|int $id
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return bool
     */
    protected function deleteFromCache(?int $id): bool
    {
        return $id !== null
            ? $this->cache->delete("{$id}")
            : false;
    }

    /**
     * Retrieves from the repository all entities specified by their IDs,
     * and stores them in the cache.
     *
     * @param ObjectRepository $repository
     * @param array            $ids
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return int Number of entities pushed to the cache.
     */
    protected function warmupCache(ObjectRepository $repository, array $ids): int
    {
        $entities = $repository->findBy(['id' => $ids]);

        foreach ($entities as $entity) {
            $this->cache->set("{$entity->id}", $entity);
        }

        return count($entities);
    }
}
