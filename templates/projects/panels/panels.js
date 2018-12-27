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
import List  from 'components/panel/list.vue';
import Panel from 'components/panel/panel.vue';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

// State types.
const STATE_INITIAL      = 'initial';
const STATE_INTERMEDIATE = 'intermediate';
const STATE_FINAL        = 'final';

// State responsibility values.
const STATE_KEEP   = 'keep';
const STATE_ASSIGN = 'assign';
const STATE_REMOVE = 'remove';

/**
 * 'Projects' page (left side with panels).
 */
new Vue({
    el: '#vue-panels',

    created() {
        eTraxis.store.dispatch('projects/load');
        eTraxis.store.dispatch('groups/load');
    },

    components: {
        'list': List,
        'modal': Modal,
        'panel': Panel,
    },

    data: {
        values: {},     // form values
        errors: {},     // form errors
    },

    computed: {

        /**
         * @returns {null|string} Current right-side application.
         */
        applicationId() {
            return eTraxis.store.getters.applicationId;
        },

        /**
         * @returns {Array} All existing projects.
         */
        projects() {
            return eTraxis.store.state.projects.list;
        },

        /**
         * @returns {Array} All existing global groups.
         */
        globalGroups() {
            return eTraxis.store.state.groups.global;
        },

        /**
         * @returns {Array} All local groups of the selected project.
         */
        localGroups() {
            return eTraxis.store.state.groups.local;
        },

        /**
         * @returns {Array} All templates of the current project.
         */
        templates() {
            return eTraxis.store.state.templates.list;
        },

        /**
         * @returns {Array} All states of the current template.
         */
        states() {
            return eTraxis.store.state.states.list;
        },

        /**
         * @returns {Array} Initial states of the current template.
         */
        initialStates() {
            return eTraxis.store.state.states.list.filter(state => state.type === STATE_INITIAL);
        },

        /**
         * @returns {Array} Intermediate states of the current template.
         */
        intermediateStates() {
            return eTraxis.store.state.states.list.filter(state => state.type === STATE_INTERMEDIATE);
        },

        /**
         * @returns {Array} Final states of the current template.
         */
        finalStates() {
            return eTraxis.store.state.states.list.filter(state => state.type === STATE_FINAL);
        },

        /**
         * @returns {Array} All fields of the current state.
         */
        fields() {
            return eTraxis.store.state.fields.list;
        },

        /**
         * @returns {null|number} Currently selected project.
         */
        projectId: {
            get() {
                return eTraxis.store.state.projects.currentId;
            },
            set(value) {
                eTraxis.store.commit('projects/current', value);
            },
        },

        /**
         * @returns {null|number} Currently selected group.
         */
        groupId: {
            get() {
                return eTraxis.store.state.groups.currentId;
            },
            set(value) {
                eTraxis.store.commit('groups/current', value);
            },
        },

        /**
         * @returns {null|number} Currently selected template.
         */
        templateId: {
            get() {
                return eTraxis.store.state.templates.currentId;
            },
            set(value) {
                eTraxis.store.commit('templates/current', value);
            },
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
         * @returns {null|number} Currently selected field.
         */
        fieldId: {
            get() {
                return eTraxis.store.state.fields.currentId;
            },
            set(value) {
                eTraxis.store.commit('fields/current', value);
            },
        },
    },

    methods: {

        /**
         * Shows 'New project' dialog.
         */
        showNewProjectDialog() {

            this.values = {
                suspended: true,
            };

            this.errors = {};

            this.$refs.dlgNewProject.open();
        },

        /**
         * Creates new project.
         */
        createProject() {

            let data = {
                name: this.values.name,
                description: this.values.description,
                suspended: this.values.suspended,
            };

            ui.block();

            axios.post(url('/api/projects'), data)
                .then(async response => {
                    this.$refs.dlgNewProject.close();
                    await eTraxis.store.dispatch('projects/load')
                        .then(() => {
                            let location = response.headers.location;
                            this.projectId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'New group' dialog.
         */
        showNewGroupDialog() {

            this.values = {
                global: false,
            };

            this.errors = {};

            this.$refs.dlgNewGroup.open();
        },

        /**
         * Creates new group.
         */
        createGroup() {

            let data = {
                project: this.values.global ? null : this.projectId,
                name: this.values.name,
                description: this.values.description,
            };

            ui.block();

            axios.post(url('/api/groups'), data)
                .then(async response => {
                    this.$refs.dlgNewGroup.close();
                    await eTraxis.store.dispatch('groups/load', data.project)
                        .then(() => {
                            let location = response.headers.location;
                            this.groupId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'New template' dialog.
         */
        showNewTemplateDialog() {

            this.values = {};
            this.errors = {};

            this.$refs.dlgNewTemplate.open();
        },

        /**
         * Creates new template.
         */
        createTemplate() {

            let data = {
                project: this.projectId,
                name: this.values.name,
                prefix: this.values.prefix,
                description: this.values.description,
                criticalAge: this.values.criticalAge,
                frozenTime: this.values.frozenTime,
            };

            ui.block();

            axios.post(url('/api/templates'), data)
                .then(async response => {
                    this.$refs.dlgNewTemplate.close();
                    await eTraxis.store.dispatch('templates/load', data.project)
                        .then(() => {
                            let location = response.headers.location;
                            this.templateId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'New state' dialog.
         */
        showNewStateDialog() {

            let template = eTraxis.store.state.templates.list
                .filter(template => template.id === this.templateId)
                .pop();

            if (template.class === null) {
                ui.info(i18n['template.must_be_locked']);
                return;
            }

            this.values = {
                type: STATE_INTERMEDIATE,
                responsible: STATE_KEEP,
            };

            this.errors = {};

            this.$refs.dlgNewState.open();
        },

        /**
         * Creates new state.
         */
        createState() {

            let data = {
                template: this.templateId,
                name: this.values.name,
                type: this.values.type,
                responsible: this.values.type === STATE_FINAL ? STATE_REMOVE : this.values.responsible,
                nextState: this.values.type === STATE_FINAL ? null : this.values.nextState,
            };

            ui.block();

            axios.post(url('/api/states'), data)
                .then(async response => {
                    this.$refs.dlgNewState.close();
                    await eTraxis.store.dispatch('states/load', data.template)
                        .then(() => {
                            let location = response.headers.location;
                            this.stateId = parseInt(location.substr(location.lastIndexOf('/') + 1));
                        });
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
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

            if (id !== null) {
                eTraxis.store.dispatch('groups/load', id);
                eTraxis.store.dispatch('templates/load', id);
            }
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
                eTraxis.store.dispatch('states/load', id);
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
                eTraxis.store.dispatch('fields/load', id);
            }
        },
    },
});
