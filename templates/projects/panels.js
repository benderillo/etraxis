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

import List  from 'components/panel/list.vue';
import Panel from 'components/panel/panel.vue';
import store from './store';

// State types.
const STATE_INITIAL      = 'initial';
const STATE_INTERMEDIATE = 'intermediate';
const STATE_FINAL        = 'final';

/**
 * 'Projects' page (left side with panels).
 */
new Vue({
    el: '#vue-panels',

    created() {
        store.dispatch('projects/load');
        store.dispatch('groups/load');
    },

    components: {
        'list': List,
        'panel': Panel,
    },

    computed: {

        /**
         * @returns {Array} All existing projects.
         */
        projects() {
            return store.state.projects.list;
        },

        /**
         * @returns {Array} All existing global groups.
         */
        globalGroups() {
            return store.state.groups.global;
        },

        /**
         * @returns {Array} All local groups of the selected project.
         */
        localGroups() {
            return store.state.groups.local;
        },

        /**
         * @returns {Array} All templates of the current project.
         */
        templates() {
            return store.state.templates.list;
        },

        /**
         * @returns {Array} Initial states of the current template.
         */
        initialStates() {
            return store.state.states.list.filter(state => state.type === STATE_INITIAL);
        },

        /**
         * @returns {Array} Intermediate states of the current template.
         */
        intermediateStates() {
            return store.state.states.list.filter(state => state.type === STATE_INTERMEDIATE);
        },

        /**
         * @returns {Array} Final states of the current template.
         */
        finalStates() {
            return store.state.states.list.filter(state => state.type === STATE_FINAL);
        },

        /**
         * @returns {Array} All fields of the current state.
         */
        fields() {
            return store.state.fields.list;
        },

        /**
         * @returns {null|number} Currently selected project.
         */
        projectId: {
            get() {
                return store.state.projects.currentId;
            },
            set(value) {
                store.commit('projects/current', value);
            },
        },

        /**
         * @returns {null|number} Currently selected group.
         */
        groupId: {
            get() {
                return store.state.groups.currentId;
            },
            set(value) {
                store.commit('groups/current', value);
            },
        },

        /**
         * @returns {null|number} Currently selected template.
         */
        templateId: {
            get() {
                return store.state.templates.currentId;
            },
            set(value) {
                store.commit('templates/current', value);
            },
        },

        /**
         * @returns {null|number} Currently selected state.
         */
        stateId: {
            get() {
                return store.state.states.currentId;
            },
            set(value) {
                store.commit('states/current', value);
            },
        },

        /**
         * @returns {null|number} Currently selected field.
         */
        fieldId: {
            get() {
                return store.state.fields.currentId;
            },
            set(value) {
                store.commit('fields/current', value);
            },
        },
    },

    watch: {

        /**
         * Project has been selected.
         *
         * @param {number} id Project ID.
         */
        projectId(id) {

            this.groupId    = null;
            this.templateId = null;

            store.dispatch('groups/load', id);
            store.dispatch('templates/load', id);
        },

        /**
         * Group has been selected.
         *
         * @param {number} id Group ID.
         */
        groupId(id) {

            if (id !== null) {
                this.templateId = null;
            }
        },

        /**
         * Template has been selected.
         *
         * @param {number} id Template ID.
         */
        templateId(id) {

            this.stateId = null;

            if (id !== null) {
                this.groupId = null;
                store.dispatch('states/load', id);
            }
        },

        /**
         * State has been selected.
         *
         * @param {number} id State ID.
         */
        stateId(id) {

            this.fieldId = null;

            if (id !== null) {
                store.dispatch('fields/load', id);
            }
        },
    },
});
