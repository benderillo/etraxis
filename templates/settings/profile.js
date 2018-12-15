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
 * 'Profile' form ('Settings' page).
 */
new Vue({
    el: '#vue-profile',

    created() {
        eTraxis.store.loadProfile();
    },

    data: {
        disabled: eTraxis.external,     // whether the form is disabled
        values: eTraxis.store.profile,  // form values
        errors: {},                     // form errors
    },

    methods: {

        /**
         * Saves the changes.
         */
        saveChanges() {

            if (this.disabled) {
                return;
            }

            ui.block();

            axios.patch(url('/api/my/profile'), this.values)
                .then(() => {
                    this.errors = {};
                    ui.info(i18n['text.changes_saved']);
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },
    },
});
