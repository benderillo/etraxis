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

import Tab   from 'components/tabs/tab.vue';
import Tabs  from 'components/tabs/tabs.vue';
import epoch from 'utilities/epoch';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * 'Projects' page (project view).
 */
new Vue({
    el: '#vue-project',

    components: {
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        project: {},    // project info
    },

    computed: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId() {
            return eTraxis.store.getters.applicationId;
        },

        /**
         * @returns {null|number} Currently selected project.
         */
        projectId() {
            return eTraxis.store.state.projects.currentId;
        },

        /**
         * @returns {null|string} Human-readable start date.
         */
        startDate() {
            return this.project.created ? epoch.date(this.project.created) : null;
        },
    },

    watch: {

        /**
         * Another project has been selected.
         *
         * @param {null|number} id Project ID.
         */
        projectId(id) {

            if (id !== null) {
                axios.get(url(`/api/projects/${this.projectId}`))
                    .then(response => this.project = response.data)
                    .catch(exception => ui.getErrors(exception));
            }
        }
    },
});
