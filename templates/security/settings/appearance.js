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
 * 'Appearance' form ('Settings' page).
 */
new Vue({
    el: '#vue-appearance',
    delimiters: ['${', '}'],

    data: {

        // Form values.
        values: eTraxis.store.profile,

        // Form errors.
        errors: {},
    },

    methods: {

        /**
         * Saves the changes.
         */
        saveChanges() {

            ui.block();

            axios.patch(url('/api/my/profile'), this.values)
                .then(() => {
                    ui.info(i18n['text.changes_saved'], () => {
                        location.href = url('/settings');
                    });
                })
                .catch(exception => {
                    this.errors = ui.getErrors(exception);
                    ui.unblock();
                });
        },
    },
});
