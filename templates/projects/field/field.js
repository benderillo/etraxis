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
 * 'Projects' page (field view).
 */
new Vue({
    el: '#vue-field',

    components: {
        'modal': Modal,
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        field: {},          // field info
        permissions: {},    // field permissions
        items: {},          // field items (for 'list' fields only)
        values: {},         // form values
        errors: {},         // form errors
    },

    computed: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId() {
            return eTraxis.store.getters.applicationId;
        },

        /**
         * @returns {null|number} Currently selected field.
         */
        fieldId: {
            get() {
                return eTraxis.store.state.fields.currentId;
            },
            set(value) {
                eTraxis.store.commit('fields/current', value);
            },
        },

        /**
         * @returns {string} Human-readable field type.
         */
        type() {
            return i18n[eTraxis.field_types[this.field.type]];
        },

        /**
         * @returns {string} Human-readable field's default value.
         */
        defaultValue() {

            if (this.field.default !== null) {

                if (this.field.type === 'checkbox') {
                    return this.field.default ? i18n['field.on'] : i18n['field.off'];
                }

                if (this.field.type === 'list') {
                    return this.field.default.text;
                }
            }

            return this.field.default;
        },
    },

    methods: {

        /**
         * Reloads field info.
         */
        reloadField() {

            ui.block();

            this.items = {};
            this.permissions = {};

            axios.get(url(`/api/fields/${this.fieldId}`))
                .then(response => {
                    this.field = response.data;
                    eTraxis.store.commit('fields/update', this.field);
                })
                .then(() => {
                    axios.get(url(`/admin/fields/permissions/${this.fieldId}`))
                        .then(response => this.permissions = response.data);
                })
                .then(() => {
                    if (this.field.type === 'list') {
                        axios.get(url(`/api/fields/${this.fieldId}/items`))
                            .then(response => this.items = response.data);
                    }
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Edit field' dialog.
         */
        showEditFieldDialog() {

            this.values = {
                type: this.field.type,
                name: this.field.name,
                description: this.field.description,
                required: this.field.required,
                maxlength: this.field.maxlength,
                minimum: this.field.minimum,
                maximum: this.field.maximum,
                default: (this.field.type === 'list' && this.field.default !== null)
                         ? this.field.default.id
                         : this.field.default,
            };

            this.errors = {};

            this.$refs.dlgEditField.open();
        },

        /**
         * Updates the field.
         */
        updateField() {

            let data = {
                name: this.values.name,
                description: this.values.description,
                required: this.values.required,
                maxlength: this.values.maxlength,
                minimum: this.values.minimum,
                maximum: this.values.maximum,
                default: this.values.default,
            };

            ui.block();

            axios.put(url(`/api/fields/${this.fieldId}`), data)
                .then(() => {
                    this.reloadField();
                    this.$refs.dlgEditField.close();
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the field.
         */
        deleteField() {

            ui.confirm(i18n['confirm.field.delete'], () => {

                ui.block();

                axios.delete(url(`/api/fields/${this.fieldId}`))
                    .then(() => {
                        eTraxis.store.dispatch('fields/load', this.field.state.id);
                        this.fieldId = null;
                    })
                    .catch(exception => ui.getErrors(exception))
                    .then(() => ui.unblock());
            });
        },
    },

    watch: {

        /**
         * Another field has been selected.
         *
         * @param {null|number} id Field ID.
         */
        fieldId(id) {

            if (id !== null) {
                this.reloadField();
            }
        }
    },
});
