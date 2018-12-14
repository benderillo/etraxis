<template>
    <dialog>
        <form @submit.prevent>
            <div class="modal-header">
                <span class="pull-after fa fa-remove" :title="text.close" @click="onCancel"></span>
                <div>{{ header }}</div>
            </div>
            <div class="modal-body">
                <slot></slot>
            </div>
            <div class="modal-footer text-right">
                <button type="submit" @click="onSubmit">{{ text.ok }}</button>
                <button type="button" @click="onCancel">{{ text.cancel }}</button>
            </div>
        </form>
    </dialog>
</template>

<script>

    /**
     * Modal dialog.
     */
    export default {

        mounted() {

            dialogPolyfill.registerDialog(this.$el);

            this.$el.addEventListener('cancel', () => {
                this.$emit('cancel');
            });
        },

        props: {

            /**
             * Header text.
             */
            header: {
                type: String,
                required: true,
            },
        },

        data: () => ({
            text: {
                close: i18n['button.close'],
                ok: i18n['button.ok'],
                cancel: i18n['button.cancel'],
            },
        }),

        methods: {

            /**
             * Opens the dialog.
             */
            open() {
                this.$el.showModal();
            },

            /**
             * Closes the dialog.
             */
            close() {
                this.$el.close();
            },

            /**
             * Dialog is submitted (doesn't close automatically).
             */
            onSubmit() {
                this.$emit('submit');
            },

            /**
             * Dialog is cancelled (closes automatically).
             */
            onCancel() {
                this.$el.close();
                this.$emit('cancel');
            },
        },
    };

</script>
