<template>
    <div class="panel-body">
        <div v-for="item in items" :key="item.id" class="panel-item" :class="[ item.class, item.id === selected ? 'selected' : null ]">
            <span class="fa fa-check"></span>
            <a href="#" @click.prevent="$emit('click', item.id)">{{ item.title }}</a>
        </div>
        <div v-if="items.length === 0" class="panel-item empty">
            <span class="fa fa-check"></span>
            <a>&mdash;</a>
        </div>
    </div>
</template>

<script>

    /**
     * Panel list.
     */
    export default {

        props: {

            /**
             * Items are objects with the following keys: id, title, class (optional).
             */
            items: {
                type: Array,
                required: true,
            },

            /**
             * ID of the currently selected item (can be null).
             */
            selected: {
                required: false,
                default: null,
            },
        },
    };

</script>
