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
 * A user page.
 */
new Vue({
    el: '#vue-user',

    created() {
        this.reloadProfile();
    },

    components: {
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        profile: {},    // user's profile
    },

    computed: {

        /**
         * Returns human-readable provider.
         *
         * @returns {string}
         */
        provider() {
            return eTraxis.providers[this.profile.provider];
        },

        /**
         * Returns human-readable language.
         *
         * @returns {string}
         */
        language() {
            return eTraxis.locales[this.profile.locale];
        },
    },

    methods: {

        /**
         * Reloads user's profile.
         */
        reloadProfile() {
            axios.get(url(`/api/users/${eTraxis.userId}`))
                .then(response => this.profile = response.data)
                .catch(exception => ui.getErrors(exception));
        },

        /**
         * Redirects back to list of users.
         */
        goBack() {
            location.href = url('/admin/users');
        },

        /**
         * Deletes the user.
         */
        deleteUser() {

            ui.confirm(i18n['confirm.user.delete'], () => {

                ui.block();

                axios.delete(url(`/api/users/${eTraxis.userId}`))
                    .then(() => {
                        location.href = url('/admin/users');
                    })
                    .catch(exception => ui.getErrors(exception))
                    .then(() => {
                        ui.unblock();
                    });
            });
        },

        /**
         * Disables the user.
         */
        disableUser() {

            ui.block();

            let data = {
                users: [eTraxis.userId],
            };

            axios.post(url('/api/users/disable'), data)
                .then(() => {
                    this.reloadProfile();
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => {
                    ui.unblock();
                });
        },

        /**
         * Enables the user.
         */
        enableUser() {

            ui.block();

            let data = {
                users: [eTraxis.userId],
            };

            axios.post(url('/api/users/enable'), data)
                .then(() => {
                    this.reloadProfile();
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => {
                    ui.unblock();
                });
        },

        /**
         * Unlocks the user.
         */
        unlockUser() {

            ui.block();

            axios.post(url(`/api/users/${eTraxis.userId}/unlock`))
                .then(() => {
                    this.reloadProfile();
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => {
                    ui.unblock();
                });
        },
    },
});
