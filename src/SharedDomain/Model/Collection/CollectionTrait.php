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

namespace eTraxis\SharedDomain\Model\Collection;

use Symfony\Component\HttpFoundation\Request;

/**
 * A trait with some utility functions to work with a collection of entities.
 */
trait CollectionTrait
{
    /**
     * Retrieves and sanitizes 'offset', 'limit' and related headers from specified request and
     * returns a collection from specified repository, using the retrieved parameters.
     *
     * @param Request             $request
     * @param CollectionInterface $repository
     *
     * @return Collection
     */
    protected function getCollection(Request $request, CollectionInterface $repository): Collection
    {
        $offset = (int) $request->get('offset', 0);
        $limit  = (int) $request->get('limit', CollectionInterface::MAX_LIMIT);

        $offset = max(0, $offset);
        $limit  = max(1, min($limit, CollectionInterface::MAX_LIMIT));

        $search = $request->headers->get('X-Search');
        $filter = json_decode($request->headers->get('X-Filter'), true);
        $sort   = json_decode($request->headers->get('X-Sort'), true);

        return $repository->getCollection($offset, $limit, $search, $filter ?? [], $sort ?? []);
    }
}
