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

        permissions: {},    // group permissions
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
        groupId: {
            get() {
                return eTraxis.store.state.groups.currentId;
            },
            set(value) {
                eTraxis.store.commit('groups/current', value);
            },
        },
    },

    methods: {

        /**
         * Reloads group info.
         */
        reloadGroup() {

            ui.block();

            this.permissions = {};

            axios.get(url(`/api/groups/${this.groupId}`))
                .then(response => {
                    this.group = response.data;
                    eTraxis.store.commit('groups/update', this.group);
                })
                .then(() => {
                    axios.get(url(`/admin/groups/permissions/${this.groupId}`))
                        .then(response => this.permissions = response.data);
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the group.
         */
        deleteGroup() {

            ui.confirm(i18n['confirm.group.delete'], () => {

                ui.block();

                axios.delete(url(`/api/groups/${this.groupId}`))
                    .then(() => {
                        if (this.group.global) {
                            eTraxis.store.dispatch('groups/load');
                        }
                        else {
                            eTraxis.store.dispatch('groups/load', this.group.project.id);
                        }
                        this.groupId = null;
                    })
                    .catch(exception => ui.getErrors(exception))
                    .then(() => ui.unblock());
            });
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
                this.reloadGroup();
            }
        }
    },
});
