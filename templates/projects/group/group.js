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

import Tab  from 'components/tabs/tab.vue';
import Tabs from 'components/tabs/tabs.vue';
import ui   from 'utilities/ui';
import url  from 'utilities/url';

/**
 * 'Projects' page (group view).
 */
new Vue({
    el: '#vue-group',

    components: {
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {

        // Group info.
        group: {
            project: {},
        },
    },

    computed: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId() {
            return eTraxis.store.getters.applicationId;
        },

        /**
         * @returns {null|number} Currently selected group.
         */
        groupId() {
            return eTraxis.store.state.groups.currentId;
        },
    },

    watch: {

        /**
         * Another group has been selected.
         *
         * @param {null|number} id Group ID.
         */
        groupId(id) {

            if (id !== null) {
                axios.get(url(`/api/groups/${this.groupId}`))
                    .then(response => this.group = response.data)
                    .catch(exception => ui.getErrors(exception));
            }
        }
    },
});
