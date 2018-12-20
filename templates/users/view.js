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

import Modal from 'components/modal.vue';
import Tab   from 'components/tabs/tab.vue';
import Tabs  from 'components/tabs/tabs.vue';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * A user page.
 */
new Vue({
    el: '#vue-user',

    created() {

        // Load user's profile.
        this.reloadProfile();

        // Load groups the user is a member of.
        this.reloadGroups();

        // Load all available groups.
        const loadAllGroups = (offset = 0) => {

            let headers = {
                'X-Sort': JSON.stringify({'project': 'ASC', 'name': 'ASC'}),
            };

            axios.get(url(`/api/groups?offset=${offset}`), {headers})
                .then(response => {

                    for (let group of response.data.data) {
                        this.allGroups.push(group);
                    }

                    if (response.data.to + 1 < response.data.total) {
                        loadAllGroups(response.data.to + 1);
                    }
                })
                .catch(exception => ui.getErrors(exception));
        };

        loadAllGroups();
    },

    components: {
        'modal': Modal,
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        profile: {},            // user's profile
        values: {},             // form values
        errors: {},             // form errors
        allGroups: [],          // all existing groups
        userGroups: [],         // groups the user is a member of
        groupsToAdd: [],        // groups selected to add
        groupsToRemove: [],     // groups selected to remove
        text: {
            global: i18n['group.global'],
        },
    },

    computed: {

        /**
         * @returns {string} Human-readable provider.
         */
        provider() {
            return eTraxis.providers[this.profile.provider];
        },

        /**
         * @returns {string} Human-readable language.
         */
        language() {
            return eTraxis.locales[this.profile.locale];
        },

        /**
         * @returns {string} Human-readable theme.
         */
        theme() {
            return eTraxis.themes[this.profile.theme];
        },

        /**
         * @returns {Array} List of all groups which the user is not a member of.
         */
        otherGroups() {

            let ids = this.userGroups.map(group => group.id);

            return this.allGroups.filter(group => ids.indexOf(group.id) === -1);
        },
    },

    methods: {

        /**
         * Reloads user's profile.
         */
        reloadProfile() {

            ui.block();

            axios.get(url(`/api/users/${eTraxis.userId}`))
                .then(response => this.profile = response.data)
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Reloads list of groups the user is a member of.
         */
        reloadGroups() {

            ui.block();

            axios.get(url(`/api/users/${eTraxis.userId}/groups`))
                .then(response => {
                    this.userGroups = response.data.sort((group1, group2) => {
                        if (group1.project === group2.project) {
                            return group1.name.localeCompare(group2.name);
                        }
                        else {
                            if (group1.project === null) {
                                return -1;
                            }
                            if (group2.project === null) {
                                return +1;
                            }
                            return group1.project.name.localeCompare(group2.project.name);
                        }
                    });
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Redirects back to list of users.
         */
        goBack() {
            location.href = url('/admin/users');
        },

        /**
         * Shows 'Edit user' dialog.
         */
        showEditUserDialog() {

            this.values = {
                fullname: this.profile.fullname,
                email: this.profile.email,
                description: this.profile.description,
                locale: this.profile.locale,
                theme: this.profile.theme,
                timezone: this.profile.timezone,
                admin: this.profile.admin,
                disabled: this.profile.disabled,
            };

            this.errors = {};

            this.$refs.dlgEditUser.open();
        },

        /**
         * Updates the user.
         */
        updateUser() {

            let data = {
                fullname: this.values.fullname,
                email: this.values.email,
                description: this.values.description,
                locale: this.values.locale,
                theme: this.values.theme,
                timezone: this.values.timezone,
                admin: this.values.admin,
                disabled: this.values.disabled,
            };

            ui.block();

            axios.put(url(`/api/users/${eTraxis.userId}`), data)
                .then(() => {
                    this.reloadProfile();
                    this.$refs.dlgEditUser.close();
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Change password' dialog.
         */
        showPasswordDialog() {

            this.values = {};
            this.errors = {};

            this.$refs.dlgPassword.open();
        },

        /**
         * Sets user's password.
         */
        setPassword() {

            if (this.values.password !== this.values.confirm) {
                ui.alert(i18n['password.dont_match']);
                return;
            }

            let data = {
                password: this.values.password,
            };

            ui.block();

            axios.put(url(`/api/users/${eTraxis.userId}/password`), data)
                .then(() => {
                    ui.info(i18n['password.changed']);
                    this.$refs.dlgPassword.close();
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
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
                    .then(() => ui.unblock());
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
                .then(() => this.reloadProfile())
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
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
                .then(() => this.reloadProfile())
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Unlocks the user.
         */
        unlockUser() {

            ui.block();

            axios.post(url(`/api/users/${eTraxis.userId}/unlock`))
                .then(() => this.reloadProfile())
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Adds the user to selected groups.
         */
        addGroups() {

            ui.block();

            let data = {
                add: this.groupsToAdd,
            };

            axios.patch(url(`/api/users/${eTraxis.userId}/groups`), data)
                .then(() => this.reloadGroups())
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Removes the user from selected groups.
         */
        removeGroups() {

            ui.block();

            let data = {
                remove: this.groupsToRemove,
            };

            axios.patch(url(`/api/users/${eTraxis.userId}/groups`), data)
                .then(() => this.reloadGroups())
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },
    },
});
