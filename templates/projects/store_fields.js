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

import ui  from 'utilities/ui';
import url from 'utilities/url';

/**
 * Fields store module.
 */
module.exports = {

    namespaced: true,

    state: {
        list: [],           // all local fields of the selected state
        currentId: null,    // currently selected field
    },

    mutations: {

        /**
         * Sets current field.
         *
         * @param {Object} state Store's state.
         * @param {number} id    Field ID.
         */
        current(state, id) {
            state.currentId = id;
        },

        /**
         * Updates specified field.
         *
         * @param {Object} state Store's state.
         * @param {Object} data  Field info.
         */
        update(state, data) {
            for (let field of state.list) {
                if (field.id === data.id) {
                    field.title = data.name;
                    break;
                }
            }
        },
    },

    actions: {

        /**
         * Loads all fields of the specified state.
         *
         * @param {Vuex.Store} context Store context.
         * @param {number}     id      State ID.
         */
        async load(context, id) {

            let headers = {
                'X-Filter': JSON.stringify({'state': id}),
                'X-Sort': JSON.stringify({'position': 'ASC'}),
            };

            let fields = [];
            let offset = 0;

            while (offset !== -1) {

                await axios.get(url(`/api/fields?offset=${offset}`), {headers})
                    .then(response => {

                        for (let field of response.data.data) {
                            fields.push({
                                id: field.id,
                                title: field.name,
                            });
                        }

                        offset = response.data.to + 1 < response.data.total
                                 ? response.data.to + 1
                                 : -1;
                    })
                    .catch(exception => {
                        offset = -1;
                        ui.getErrors(exception);
                    });
            }

            context.state.list = fields;
        },
    },
};
