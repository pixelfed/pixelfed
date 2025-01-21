<template>
    <div :class="[ isCard ? 'card shadow-none border card-body' : '' ]">
        <div
            class="form-group"
            :class="[ isInline ? 'd-flex align-items-center gap-1' : 'mb-1' ]">
            <label :for="elementId" class="font-weight-bold mb-0">{{ name }}</label>
            <input
                :id="elementId"
                class="form-control form-control-muted mb-0"
                :placeholder="placeholder"
                :value="value"
                @input="$emit('change', $event.target.value)"
                :disabled="isDisabled" />
        </div>
        <p v-if="description && description.length" class="help-text small text-muted mb-0" v-html="description">
        </p>
    </div>
</template>

<script>
    export default {
        props: {
            name: {
                type: String
            },

            value: {
                type: String
            },

            placeholder: {
                type: String
            },

            description: {
                type: String
            },

            isCard: {
                type: Boolean,
                default: true
            },

            isInline: {
                type: Boolean,
                default: false
            },

            isDisabled: {
                type: Boolean,
                default: false
            }
        },

        computed: {
            elementId: {
                get() {
                    let name = this.name;
                    name = name.toLowerCase();
                    name = name.replace(/[^a-z0-9 -]/g, ' ');
                    name = name.replace(/\s+/g, '-');
                    name = name.replace(/^-+|-+$/g, '');
                    return 'fec_' + name;
                }
            }
        }
    }
</script>

<style lang="scss" scoped="true">
    .gap-1 {
        gap: 1rem;
    }
</style>
