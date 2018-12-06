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

window.eTraxis = {};

Vue.options.delimiters = ['${', '}'];
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Makes an AJAX call for DataTable component.
 *
 * @param {string}   url
 * @param {Request}  request
 * @param {function} callback
 * @returns {Promise}
 */
axios.datatable = (url, request, callback) => {

    let headers = {
        'X-Search': request.search,
        'X-Filter': JSON.stringify(request.filters),
        'X-Sort': JSON.stringify(request.sorting),
    };

    let params = {
        offset: request.from,
        limit: request.limit,
    };

    return new Promise((resolve, reject) => {
        axios.get(url, {headers, params})
            .then(response => resolve({
                from: response.data.from,
                to: response.data.to,
                total: response.data.total,
                data: response.data.data.map(entry => callback(entry)),
            }))
            .catch(exception => reject(exception.response.data));
    });
};
