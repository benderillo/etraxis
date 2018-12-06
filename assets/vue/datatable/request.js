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

/**
 * Table request definition.
 *
 * @property {number} from    Zero-based index of the first entry to return.
 * @property {number} limit   Maximum number of entries to return.
 * @property {string} search  Current value of the global search.
 * @property {Array}  filters Current values of the column filters (Object { "column name": "string value" }).
 * @property {Array}  sorting Current sort modes (Object { "column name": "asc"|"desc" }).
 */
module.exports = class {

    /**
     * Default constructor.
     */
    constructor(from, limit, search = null, filters = [], sorting = []) {
        this.from    = from;
        this.limit   = limit;
        this.search  = search;
        this.filters = filters;
        this.sorting = sorting;
    }
};
