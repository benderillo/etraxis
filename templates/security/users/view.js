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
        // Get user's profile.
        axios.get(url('/api/users/' + eTraxis.userId))
            .then(response => this.profile = response.data)
            .catch(exception => ui.getErrors(exception));
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
         * Redirects back to list of users.
         */
        goBack() {
            location.href = url('/admin/users');
        },
    },
});
