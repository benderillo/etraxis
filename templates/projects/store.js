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

import moduleFields    from './store_fields';
import moduleGroups    from './store_groups';
import moduleProjects  from './store_projects';
import moduleStates    from './store_states';
import moduleTemplates from './store_templates';

// Right-side applications.
const APPLICATION_NONE     = null;
const APPLICATION_PROJECT  = 'project';
const APPLICATION_GROUP    = 'group';
const APPLICATION_TEMPLATE = 'template';
const APPLICATION_STATE    = 'state';
const APPLICATION_FIELD    = 'field';

/**
 * Global store for 'Projects' page.
 */
eTraxis.store = new Vuex.Store({

    modules: {
        projects: moduleProjects,
        groups: moduleGroups,
        templates: moduleTemplates,
        states: moduleStates,
        fields: moduleFields,
    },

    getters: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId: (state) => {

            if (state.fields.currentId !== null) {
                return APPLICATION_FIELD;
            }
            else if (state.states.currentId !== null) {
                return APPLICATION_STATE;
            }
            else if (state.templates.currentId !== null) {
                return APPLICATION_TEMPLATE;
            }
            else if (state.groups.currentId !== null) {
                return APPLICATION_GROUP;
            }
            else if (state.projects.currentId !== null) {
                return APPLICATION_PROJECT;
            }
            else {
                return APPLICATION_NONE;
            }
        },
    },
});
