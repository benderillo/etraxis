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

import Modal from 'components/modal.vue';
import Tab   from 'components/tabs/tab.vue';
import Tabs  from 'components/tabs/tabs.vue';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * 'Projects' page (state view).
 */
new Vue({
    el: '#vue-state',

    components: {
        'modal': Modal,
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        state: {},          // state info
        permissions: {},    // state permissions
        values: {},         // form values
        errors: {},         // form errors
    },

    computed: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId() {
            return eTraxis.store.getters.applicationId;
        },

        /**
         * @returns {Array} All states of the current template.
         */
        states() {
            return eTraxis.store.state.states.list;
        },

        /**
         * @returns {null|number} Currently selected state.
         */
        stateId: {
            get() {
                return eTraxis.store.state.states.currentId;
            },
            set(value) {
                eTraxis.store.commit('states/current', value);
            },
        },

        /**
         * @returns {string} Human-readable state type.
         */
        type() {
            return i18n[eTraxis.state_types[this.state.type]];
        },

        /**
         * @returns {string} Human-readable state responsible.
         */
        responsible() {
            return i18n[eTraxis.state_responsibles[this.state.responsible]];
        },

        /**
         * @returns {null|string} Human-readable next state.
         */
        nextState() {
            let state = eTraxis.store.state.states.list.filter(state => state.id === this.state.next_state).pop();
            return state ? state.title : null;
        },
    },

    methods: {

        /**
         * Reloads state info.
         */
        reloadState() {

            ui.block();

            this.permissions = {};

            axios.get(url(`/api/states/${this.stateId}`))
                .then(response => {
                    this.state = response.data;
                    eTraxis.store.commit('states/update', this.state);
                })
                .then(() => {
                    axios.get(url(`/admin/states/permissions/${this.stateId}`))
                        .then(response => this.permissions = response.data);
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Edit state' dialog.
         */
        showEditStateDialog() {

            this.values = {
                name: this.state.name,
                type: this.state.type,
                responsible: this.state.responsible,
                nextState: this.state.next_state,
            };

            this.errors = {};

            this.$refs.dlgEditState.open();
        },

        /**
         * Updates the state.
         */
        updateState() {

            let data = {
                name: this.values.name,
                responsible: this.values.responsible,
                nextState: this.values.nextState,
            };

            ui.block();

            axios.put(url(`/api/states/${this.stateId}`), data)
                .then(() => {
                    this.reloadState();
                    this.$refs.dlgEditState.close();
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the state.
         */
        deleteState() {

            ui.confirm(i18n['confirm.state.delete'], () => {

                ui.block();

                axios.delete(url(`/api/states/${this.stateId}`))
                    .then(() => {
                        eTraxis.store.dispatch('states/load', this.state.template.id);
                        this.stateId = null;
                    })
                    .catch(exception => ui.getErrors(exception))
                    .then(() => ui.unblock());
            });
        },

        /**
         * Makes the state an initial one.
         */
        setInitial() {

            ui.block();

            axios.post(url(`/api/states/${this.stateId}/initial`))
                .then(() => {
                    eTraxis.store.dispatch('states/load', this.state.template.id);
                    this.reloadState();
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },
    },

    watch: {

        /**
         * Another state has been selected.
         *
         * @param {null|number} id State ID.
         */
        stateId(id) {

            if (id !== null) {
                this.reloadState();
            }
        }
    },
});
