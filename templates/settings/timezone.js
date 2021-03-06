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

    created() {
        this.loadCities();
    },

    data: {
        cities: [],                     // list of all cities of the current country
        values: eTraxis.store.profile,  // form values (timezone)
        country: eTraxis.country,       // form values (country)
        errors: {},                     // form errors
    },

    methods: {

        /**
         * Saves the changes.
         */
        saveChanges() {

            ui.block();

            axios.patch(url('/api/my/profile'), this.values)
                .then(() => {
                    this.errors = {};
                    ui.info(i18n['text.changes_saved']);
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
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
                    .catch(exception => ui.getErrors(exception));
            }
        },
    },

    watch: {

        /**
         * The country has been changed.
         */
        country() {
            this.loadCities();
        },
    },
});
