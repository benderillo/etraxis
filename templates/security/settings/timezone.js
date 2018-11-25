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
 * 'Timezone' form ('Settings' page).
 */
new Vue({
    el: '#vue-timezone',
    delimiters: ['${', '}'],

    created() {
        this.loadCities();
    },

    data: {

        // List of all cities of the current country.
        cities: [],

        // Form values.
        values: eTraxis.store.profile,
        country: eTraxis.country,

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
                    ui.info(i18n['text.changes_saved']);
                })
                .catch(exception => {
                    this.errors = ui.getErrors(exception);
                })
                .then(() => {
                    ui.unblock();
                });
        },

        /**
         * Loads list of cities for the current country.
         */
        loadCities() {

            if (this.country === 'UTC') {
                this.cities = { 'UTC': 'UTC' };
                this.values.timezone = 'UTC';
            }
            else {

                axios.get(url('/settings/cities/' + this.country))
                    .then(response => {
                        this.cities = response.data;
                        this.values.timezone = (this.country === eTraxis.country) ? this.values.timezone : Object.keys(response.data)[0];
                    })
                    .catch(exception => {
                        ui.getErrors(exception);
                    });
            }
        },
    },

    watch: {

        /**
         * Reloads list of cities when the country is changed.
         */
        country() {
            this.loadCities();
        },
    },
});
