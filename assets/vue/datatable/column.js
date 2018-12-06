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
 * Column definition.
 *
 * @property {string}  name       Column's unique ID.
 * @property {string}  title      Column's title.
 * @property {boolean} filterable Whether the table can be filtered by this column.
 * @property {boolean} sortable   Whether the table can be sorted by this column.
 * @property {string}  width      Desired width of the column.
 * @property {Array}   filter     List of possible values for dropdown filter (Object { "option value": "option text" }).
 */
module.exports = class {

    /**
     * Default constructor.
     */
    constructor(name, title, width = null) {
        this.name       = name;
        this.title      = title;
        this.filterable = true;
        this.sortable   = true;
        this.width      = width;
        this.filter     = [];
    }
};
