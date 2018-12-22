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
 * 'Projects' page (field view).
 */
new Vue({
    el: '#vue-field',

    components: {
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        field: {},          // field info
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
        fieldId() {
            return eTraxis.store.state.fields.currentId;
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
            return this.field.type === 'list' && this.field.default !== null
                ? `${this.field.default.value} (${this.field.default.text})`
                : this.field.default;
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
                axios.get(url(`/api/fields/${this.fieldId}`))
                    .then(response => this.field = response.data)
                    .catch(exception => ui.getErrors(exception));
            }
        }
    },
});
