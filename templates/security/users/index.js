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

import Column    from 'components/datatable/column';
import DataTable from 'components/datatable/datatable.vue';
import Request   from 'components/datatable/request';
import Modal     from 'components/modal.vue';
import ui        from 'utilities/ui';
import url       from 'utilities/url';

/**
 * 'Users' page.
 */
new Vue({
    el: '#vue-users',

    created() {

        // 'Permissions' column filter.
        this.columns[2].filter = {
            1: i18n['role.admin'],
            0: i18n['role.user'],
        };

        // 'Authentication' column filter.
        this.columns[3].filter = {
            'etraxis': 'eTraxis',
            'ldap': 'LDAP',
        };
    },

    components: {
        'datatable': DataTable,
        'modal': Modal,
    },

    data: {

        // Table columns definition.
        columns: [
            new Column('fullname', i18n['user.fullname']),
            new Column('email', i18n['user.email']),
            new Column('admin', i18n['user.permissions']),
            new Column('provider', i18n['user.authentication']),
            new Column('description', i18n['user.description'], '100%'),
        ],

        checked: [],    // list of user IDs whose rows are checked

        values: {},     // form values
        errors: {},     // form errors
    },

    methods: {

        /**
         * Data provider for the table.
         *
         * @param {Request} request
         * @returns {Promise}
         */
        users(request) {

            if (request.filters.admin.length === 0) {
                delete request.filters.admin;
            }

            return axios.datatable(url('/api/users'), request, user => {

                let status = null, provider = 'Unknown';

                if (user.locked) {
                    status = 'attention';
                }
                else if (user.disabled) {
                    status = 'muted';
                }
                else if (user.expired) {
                    status = 'pending';
                }

                if (user.provider === 'etraxis') {
                    provider = 'eTraxis';
                }
                else if (user.provider === 'ldap') {
                    provider = 'LDAP';
                }

                return {
                    DT_id: user.id,
                    DT_class: status,
                    DT_checkable: user.id !== eTraxis.currentUser,
                    fullname: user.fullname,
                    email: user.email,
                    admin: user.admin ? i18n['role.admin'] : i18n['role.user'],
                    provider: provider,
                    description: user.description,
                };
            });
        },

        /**
         * A set of checked rows in the table is changed.
         *
         * @param {Array} ids List of checked rows (user IDs).
         */
        onCheck(ids) {
            this.checked = ids;
        },

        /**
         * A row with an account is clicked.
         *
         * @param {number} id Account ID.
         */
        viewUser(id) {
            location.href = url('/admin/users/' + id);
        },

        /**
         * Shows 'New user' dialog.
         */
        showNewUserDialog() {

            this.values = {
                locale: eTraxis.defaultLocale,
                theme: eTraxis.defaultTheme,
                timezone: eTraxis.defaultTimezone,
                admin: false,
                disabled: false,
            };

            this.errors = {};

            this.$refs.dlgNewUser.open();
        },

        /**
         * Creates new user.
         */
        createUser() {

            if (this.values.password !== this.values.confirm) {
                ui.alert(i18n['password.dont_match']);
                return;
            }

            ui.block();

            axios.post(url('/api/users'), this.values)
                .then(() => {
                    this.$refs.dlgNewUser.close();
                    this.$refs.users.refresh();
                })
                .catch(exception => {
                    this.errors = ui.getErrors(exception);
                })
                .then(() => {
                    ui.unblock();
                });
        },

        /**
         * Disables selected users.
         */
        disableUsers() {

            ui.block();

            let data = {
                users: this.checked,
            };

            axios.post(url('/api/users/disable'), data)
                .then(() => {
                    this.$refs.users.refresh();
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => {
                    ui.unblock();
                });
        },

        /**
         * Enables selected users.
         */
        enableUsers() {

            ui.block();

            let data = {
                users: this.checked,
            };

            axios.post(url('/api/users/enable'), data)
                .then(() => {
                    this.$refs.users.refresh();
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => {
                    ui.unblock();
                });
        },
    },
});
