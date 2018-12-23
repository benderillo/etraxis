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
import epoch from 'utilities/epoch';
import ui    from 'utilities/ui';
import url   from 'utilities/url';

/**
 * 'Projects' page (project view).
 */
new Vue({
    el: '#vue-project',

    components: {
        'modal': Modal,
        'tab': Tab,
        'tabs': Tabs,
    },

    data: {
        project: {},        // project info
        permissions: {},    // project permissions
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
         * @returns {null|string} Human-readable start date.
         */
        startDate() {
            return this.project.created ? epoch.date(this.project.created) : null;
        },
    },

    methods: {

        /**
         * Reloads project info.
         */
        reloadProject() {

            ui.block();

            this.permissions = {};

            axios.get(url(`/api/projects/${this.projectId}`))
                .then(response => {
                    this.project = response.data;
                    eTraxis.store.commit('projects/update', this.project);
                })
                .then(() => {
                    axios.get(url(`/admin/projects/permissions/${this.projectId}`))
                        .then(response => this.permissions = response.data);
                })
                .catch(exception => ui.getErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Shows 'Edit project' dialog.
         */
        showEditProjectDialog() {

            this.values = {
                name: this.project.name,
                description: this.project.description,
                suspended: this.project.suspended,
            };

            this.errors = {};

            this.$refs.dlgEditProject.open();
        },

        /**
         * Updates the project.
         */
        updateProject() {

            let data = {
                name: this.values.name,
                description: this.values.description,
                suspended: this.values.suspended,
            };

            ui.block();

            axios.put(url(`/api/projects/${this.projectId}`), data)
                .then(() => {
                    this.reloadProject();
                    this.$refs.dlgEditProject.close();
                })
                .catch(exception => (this.errors = ui.getErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the project.
         */
        deleteProject() {

            ui.confirm(i18n['confirm.project.delete'], () => {

                ui.block();

                axios.delete(url(`/api/projects/${this.projectId}`))
                    .then(() => {
                        this.projectId = null;
                        eTraxis.store.dispatch('projects/load');
                    })
                    .catch(exception => ui.getErrors(exception))
                    .then(() => ui.unblock());
            });
        },
    },

    watch: {

        /**
         * Another project has been selected.
         *
         * @param {null|number} id Project ID.
         */
        projectId(id) {

            if (id !== null) {
                this.reloadProject();
            }
        }
    },
});
