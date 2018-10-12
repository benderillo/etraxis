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

import ui  from 'utilities/ui';
import url from 'utilities/url';

/**
 * Login page.
 */
new Vue({
    el: '#vue-login',

    created() {
        if (eTraxis.error) {
            ui.alert(eTraxis.error);
        }
    },

    methods: {

        google() {
            location.href = url('/oauth/google');
        },

        github() {
            location.href = url('/oauth/github');
        },
    },
});
