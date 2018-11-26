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
 * 'Reset password' page.
 */
new Vue({
    el: '#vue-reset',

    data: {
        values: {},     // form values
        errors: {},     // form errors
    },

    methods: {

        save() {

            if (this.values.password !== this.values.confirm) {
                ui.alert(i18n['password.dont_match']);
                return;
            }

            ui.block();

            axios.post(url('/reset/' + eTraxis.token), this.values)
                .then(() => {
                    ui.info(i18n['password.changed'], () => {
                        location.href = url('/login');
                    });
                })
                .catch(exception => {
                    ui.unblock();
                    this.errors = ui.getErrors(exception);
                });
        },
    },
});
