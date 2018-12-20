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
 * Projects store module.
 */
module.exports = {

    namespaced: true,

    state: {
        list: [],           // all existing projects
        currentId: null,    // currently selected project
    },

    mutations: {

        /**
         * Sets current project.
         *
         * @param {Object} state Store's state.
         * @param {number} id    Project ID.
         */
        current(state, id) {
            state.currentId = id;
        },
    },

    actions: {

        /**
         * Loads all existing projects.
         *
         * @param {Vuex.Store} context Store context.
         */
        async load(context) {

            let headers = {
                'X-Sort': JSON.stringify({'name': 'ASC'}),
            };

            let projects = [];
            let offset = 0;

            while (offset !== -1) {

                await axios.get(url(`/api/projects?offset=${offset}`), {headers})
                    .then(response => {

                        for (let project of response.data.data) {
                            projects.push({
                                id: project.id,
                                title: project.name,
                                class: project.suspended ? 'muted' : null,
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

            context.state.list = projects;
        },
    },
};
