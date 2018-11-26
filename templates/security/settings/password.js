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
 * 'Password' form ('Settings' page).
 */
new Vue({
    el: '#vue-password',

    data: {
        values: {},     // form values
        errors: {},     // form errors
    },

    methods: {

        /**
         * Saves new password.
         */
        changePassword() {

            if (this.values.new !== this.values.confirm) {
                ui.alert(i18n['password.dont_match']);
                return;
            }

            ui.block();

            axios.put(url('/api/my/password'), this.values)
                .then(() => {
                    ui.info(i18n['password.changed'], () => {
                        this.values = {};
                    });
                })
                .catch(exception => {
                    this.errors = ui.getErrors(exception);
                })
                .then(() => {
                    ui.unblock();
                });
        },
    },
});
