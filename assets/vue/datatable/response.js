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
 * Data response definition.
 *
 * @property {number} from  Zero-based index of the first returned entry.
 * @property {number} to    Zero-based index of the last returned entry.
 * @property {number} total Total number of entries in the source.
 * @property {Array}  data  Returned entries.
 */
module.exports = class {

    /**
     * Default constructor.
     */
    constructor(from, to, total, data = []) {
        this.from  = from;
        this.to    = to;
        this.total = total;
        this.data  = data;
    }
};
