<template>
    <div class="tabs">
        <ul>
            <li v-for="tab in tabs" :id="tab.id" :class="{ 'active': tab.active }">
                <a @click="selectTab(tab.id)">{{ tab.caption }}</a>
            </li>
        </ul>
        <slot></slot>
    </div>
</template>

<script>

    /**
     * Tabs.
     */
    export default {

        created() {
            // Autoregister all tabs.
            this.tabs = this.$children;
        },

        mounted() {
            // Make the first tab active, if active tab is not specified explicitly.
            if (this.tabs.length !== 0) {
                this.selectTab(this.active ? this.active : this.tabs[0].id);
            }
        },

        props: {

            /**
             * ID of the active tab.
             */
            active: {
                type: String,
                required: false,
            },
        },

        data: () => ({
            tabs: [],       // list of tabs
        }),

        methods: {

            /**
             * Makes the specified tab active.
             *
             * @param {string} id Tab's ID.
             */
            selectTab(id) {

                this.tabs.forEach(tab => {
                    tab.active = (tab.id === id);
                });

                this.$emit('activate', id);
            },
        },

        watch: {

            /**
             * Makes the specified tab active.
             *
             * @param {string} id Tab's ID.
             */
            active(id) {
                this.selectTab(id);
            },
        },
    };

</script>
