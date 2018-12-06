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

import ui      from 'utilities/ui';
import Request from './request';

/**
 * Data table.
 */
export default {

    created() {

        // Set initial values for column filters.
        this.filters.length = this.columns.length;
        this.filters.fill('');

        // Restore saved table state (single values).
        this.page     = parseInt(this.loadState('page')) || 1;
        this.pageSize = parseInt(this.loadState('pageSize')) || 10;
        this.search   = this.loadState('search') || '';

        // Build array of columns names.
        let columns = [];

        for (let i = 0; i < this.columns.length; i++) {
            columns.push(this.columns[i].name);
        }

        // Restore saved table state (sorting).
        const sorting = this.loadState('sorting');

        for (let column in sorting) {
            if (sorting.hasOwnProperty(column) && columns.indexOf(column) !== -1) {
                this.sorting[column] = sorting[column];
            }
        }

        if (Object.keys(this.sorting).length === 0) {
            this.sorting[this.columns[0].name] = 'asc';
        }

        // Restore saved table state (filters).
        const filters = this.loadState('filters');

        for (let column in filters) {
            if (filters.hasOwnProperty(column)) {
                const index = columns.indexOf(column);
                if (index !== -1) {
                    this.filters[index] = filters[column];
                }
            }
        }

        this.refreshWithDelay();
    },

    data: () => {

        return {
            page: 1,                // current page number, one-based
            userPage: 1,            // manually entered page number, one-based
            pageSize: 10,           // page size
            from: 0,                // first row index, zero-based
            to: 0,                  // last row index, zero-based
            total: 0,               // total rows
            rows: [],               // rows data
            checked: [],            // checked rows (array of associated IDs)
            checkedAll: false,      // whether all rows are checked
            blocked: false,         // whether the table is blocked from user's interaction
            timer: null,            // refresh timer
            search: '',             // global "Search" value
            filters: [],            // column filters values
            sorting: [],            // current columns sorting
            text: {
                empty: i18n['table.empty'],
                first: i18n['page.first'],
                last: i18n['page.last'],
                next: i18n['page.next'],
                pages: i18n['table.pages'],
                pleaseWait: i18n['text.please_wait'],
                previous: i18n['page.previous'],
                refresh: i18n['button.refresh'],
                resetFilters: i18n['button.reset_filters'],
                search: i18n['button.search'],
                size: i18n['table.size'],
                status: i18n['table.status'],
            },
        };
    },

    props: {

        /**
         * Table's name to save its state.
         */
        name: {
            type: String,
            required: true,
        },

        /**
         * Table columns specification.
         * Each item is an object of the "Column" class (see "column.js").
         */
        columns: {
            type: Array,
            required: true,
        },

        /**
         * Table rows data provider.
         * The function takes a single parameter of the "Request" class (see "request.js")
         * and must return a promise which resolves an object of the "Response" class (see "response.js").
         * In case of error the promise should reject with an error message.
         */
        data: {
            type: Function,
            required: true,
        },

        /**
         * Whether to emit an event when a table row is clicked.
         */
        clickable: {
            type: Boolean,
            default: true,
        },

        /**
         * Whether to show a column with checkboxes.
         */
        checkboxes: {
            type: Boolean,
            default: true,
        },

        /**
         * Whether to show header and footer of the table.
         */
        simplified: {
            type: Boolean,
            default: false,
        },
    },

    computed: {

        /**
         * Returns status string for the table's footer.
         *
         * @returns {string}
         */
        status() {

            if (this.blocked) {
                return this.text.pleaseWait;
            }

            return this.total === 0
                   ? null
                   : this.text.status
                       .replace('%from%', this.from + 1)
                       .replace('%to%', this.to + 1)
                       .replace('%total%', this.total);
        },

        /**
         * Returns total number of pages.
         *
         * @returns {number}
         */
        pages() {
            return Math.ceil(this.total / this.pageSize);
        },

        /**
         * Counts number of filterable columns.
         *
         * @returns {number}
         */
        totalFilters() {

            const filterables = this.columns.filter(column => {
                return column.filterable;
            });

            return filterables.length;
        },
    },

    methods: {

        /**
         * Saves specified value to the local storage.
         *
         * @param {string} name
         * @param {*} value
         */
        saveState(name, value) {

            if (typeof value === 'object') {

                let values = {};

                for (let index in value) {
                    if (value.hasOwnProperty(index)) {
                        values[index] = value[index];
                    }
                }

                localStorage[`DT_${this.name}_${name}`] = JSON.stringify(values);
            }
            else {
                localStorage[`DT_${this.name}_${name}`] = JSON.stringify(value);
            }
        },

        /**
         * Retrieves value from the local storage.
         *
         * @param {string} name
         * @returns {*}
         */
        loadState(name) {
            return JSON.parse(localStorage[`DT_${this.name}_${name}`] || null);
        },

        /**
         * Reloads the table data.
         */
        refresh() {

            let filters = {};

            for (let index in this.columns) {
                if (this.columns.hasOwnProperty(index)) {
                    const column = this.columns[index];
                    filters[column.name] = this.filters[index];
                }
            }

            let sorting = {};

            for (let index in this.sorting) {
                if (this.sorting.hasOwnProperty(index)) {
                    sorting[index] = this.sorting[index];
                }
            }

            const request = new Request((this.page - 1) * this.pageSize, this.pageSize, this.search, filters, sorting);

            this.blocked    = true;
            this.checked    = [];
            this.checkedAll = false;

            this.data(request)
                .then(response => {
                    this.from  = response.from;
                    this.to    = response.to;
                    this.total = response.total;
                    this.rows  = response.data;

                    if (this.page > this.pages) {
                        this.page = this.pages || 1;
                    }

                    this.blocked = false;
                })
                .catch(error => {
                    ui.alert(error);
                    this.blocked = false;
                });
        },

        /**
         * Reloads the table data with delay.
         */
        refreshWithDelay() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.refresh, 400);
        },

        /**
         * Clears all filters.
         */
        resetFilters() {
            this.search  = '';
            this.filters = [];

            for (let i = 0; i < this.columns.length; i++) {
                this.filters.push('');
            }
        },

        /**
         * Returns list of checked rows.
         *
         * @returns {Array} Array of associated IDs.
         */
        getChecked() {
            return this.checked;
        },

        /**
         * Toggles checkbox status of the specified row.
         *
         * @param {string} id
         */
        toggleCheck(id) {

            const index = this.checked.indexOf(id);

            if (index === -1) {
                this.checked.push(id);
            }
            else {
                this.checked.splice(index, 1);
            }
        },

        /**
         * Returns current sort direction of the specified column.
         *
         * @param {string} columnName Column ID.
         * @returns {string} 'asc', 'desc', or empty.
         */
        sortDirection(columnName) {
            return this.sorting[columnName] || '';
        },

        /**
         * Toggles sorting of the clicked column.
         *
         * @param {MouseEvent} event Click event.
         */
        toggleSorting(event) {

            const target = event.target.tagName === 'TH'
                           ? event.target
                           : event.target.parentNode;

            if (target.classList.contains('sortable')) {

                const name = target.dataset.name;
                const direction = (this.sorting[name] || '') === 'asc' ? 'desc' : 'asc';

                if (event.ctrlKey) {
                    delete this.sorting[name];
                    this.sorting[name] = direction;
                    this.saveState('sorting', this.sorting);
                }
                else {
                    this.sorting = [];
                    this.sorting[name] = direction;
                }

                this.refresh();
            }
        },
    },

    watch: {

        /**
         * Reloads the table when current page is changed.
         */
        page() {
            this.userPage = this.page;
            this.saveState('page', this.page);
            this.refresh();
        },

        /**
         * Validates page number entered by user.
         *
         * @param {number} value
         */
        userPage(value) {
            if (typeof value === 'number' && value >= 1 && value <= this.pages) {
                this.page = value;
            }
            else {
                this.userPage = this.page;
            }
        },

        /**
         * Reloads the table when page size is changed.
         */
        pageSize(value) {

            if ([10, 20, 50, 100].indexOf(value) === -1) {
                this.pageSize = 10;
                return;
            }

            this.saveState('pageSize', this.pageSize);
            this.refreshWithDelay();
        },

        /**
         * Checks or unchecks all rows.
         *
         * @param {boolean} value
         */
        checkedAll(value) {

            const rows = this.rows.filter(row => {
                return row.DT_checkable !== false;
            });

            if (!value && this.checked.length === rows.length) {
                this.checked = [];
            }

            if (value) {
                this.checked = [];
                rows.forEach(row => {
                    this.checked.push(row.DT_id);
                });
            }
        },

        /**
         * Updates "checkedAll" variable if required.
         *
         * @param {Array} value
         */
        checked(value) {

            const rows = this.rows.filter(row => {
                return row.DT_checkable !== false;
            });

            if (this.checkedAll && rows.length !== 0 && value.length === rows.length - 1) {
                this.checkedAll = false;
            }

            if (!this.checkedAll && rows.length !== 0 && value.length === rows.length) {
                this.checkedAll = true;
            }

            this.$emit('check', this.checked);
        },

        /**
         * Reloads the table when the global search value is changed.
         */
        search() {
            this.saveState('search', this.search);
            this.refreshWithDelay();
        },

        /**
         * Reloads the table when a column filter is changed.
         */
        filters() {

            let filters = {};

            for (let i = 0; i < this.columns.length; i++) {
                filters[this.columns[i].name] = this.filters[i];
            }

            this.saveState('filters', filters);
            this.refreshWithDelay();
        },

        /**
         * Reloads the table when sorting is changed.
         */
        sorting() {
            this.saveState('sorting', this.sorting);
            this.refresh();
        },
    },
};
