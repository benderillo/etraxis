<template>
    <div class="tab" v-show="active">
        <slot></slot>
    </div>
</template>

<script>

    /**
     * A single tab.
     */
    export default {

        props: {

            /**
             * Tab's unique ID.
             */
            id: {
                type: String,
                required: true,
            },

            /**
             * Tab's title.
             */
            title: {
                required: true,
            },

            /**
             * Optional counter value to be displayed in the caption.
             */
            counter: {
                type: Number,
                required: false,
            },
        },

        data: () => ({
            active: false,      // whether the tab is active
        }),

        computed: {

            /**
             * Returns full caption of the tab.
             */
            caption() {
                return typeof this.counter === 'undefined'
                       ? this.title
                       : `${this.title} (${this.counter})`;
            },
        },
    };

</script>
