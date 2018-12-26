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
 * 'Projects' page (template view).
 */
new Vue({
    el: '#vue-template',

    components: {
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        template: {},       // template info
        permissions: {},    // template permissions
    },

    computed: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId() {
            return eTraxis.store.getters.applicationId;
        },

        /**
         * @returns {null|number} Currently selected template.
         */
        templateId: {
            get() {
                return eTraxis.store.state.templates.currentId;
            },
            set(value) {
                eTraxis.store.commit('templates/current', value);
            },
        },
    },

    methods: {

        /**
         * Reloads template info.
         */
        reloadTemplate() {

            ui.block();

            this.permissions = {};

            axios.get(url(`/api/templates/${this.templateId}`))
                .then(response => {
                    this.template = response.data;
                    eTraxis.store.commit('templates/update', this.template);
                })
                .then(() => {
                    axios.get(url(`/admin/templates/permissions/${this.templateId}`))
                        .then(response => this.permissions = response.data);
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the template.
         */
        deleteTemplate() {

            ui.confirm(i18n['confirm.template.delete'], () => {

                ui.block();

                axios.delete(url(`/api/templates/${this.templateId}`))
                    .then(() => {
                        eTraxis.store.dispatch('templates/load', this.template.project.id);
                        this.templateId = null;
                    })
                    .catch(exception => ui.getErrors(exception))
                    .then(() => ui.unblock());
            });
        },
    },

    watch: {

        /**
         * Another template has been selected.
         *
         * @param {null|number} id Template ID.
         */
        templateId(id) {

            if (id !== null) {
                this.reloadTemplate();
            }
        }
    },
});
