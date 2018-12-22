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
        templateId() {
            return eTraxis.store.state.templates.currentId;
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
                axios.get(url(`/api/templates/${this.templateId}`))
                    .then(response => this.template = response.data)
                    .catch(exception => ui.getErrors(exception));
            }
        }
    },
});
