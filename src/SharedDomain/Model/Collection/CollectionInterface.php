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

/**
 * Interface to an entities collection.
 */
interface CollectionInterface
{
    public const SORT_ASC  = 'ASC';
    public const SORT_DESC = 'DESC';

    public const MAX_LIMIT = 100;

    /**
     * Returns a collection of entities as requested.
     *
     * @param int         $offset Zero-based index of the first entity to return.
     * @param int         $limit  Maximum number of entities to return.
     * @param null|string $search Optional search value.
     * @param array       $filter Array of property filters (keys are property names, values are filtering values).
     * @param array       $sort   Sorting specification (keys are property names, values are "asc" or "desc").
     *
     * @return Collection
     */
    public function getCollection(int $offset = 0, int $limit = self::MAX_LIMIT, ?string $search = null, array $filter = [], array $sort = []): Collection;
}
