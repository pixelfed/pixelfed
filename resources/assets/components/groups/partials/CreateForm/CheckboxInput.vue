<template>
    <div class="form-group row">
        <div class="col-sm-3">
            <label class="col-form-label text-left">{{ label }}</label>
        </div>
        <div class="col-sm-9">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="value">
                <label
                    class="form-check-label ml-1"
                    :class="[ strongText ? 'font-weight-bold text-capitalize text-dark' : 'small text-muted' ]"
                >
                {{ inputText }}
                </label>
            </div>

            <div
                v-if="helpText"
                class="help-text small text-muted d-flex flex-row justify-content-between gap-3">
                <div v-if="helpText">{{ helpText }}</div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            label: {
                type: String
            },
            inputText: {
                type: String
            },
            val: {
                type: String
            },
            helpText: {
                type: String
            },
            strongText: {
                type: Boolean,
                default: true
            }
        },

        data() {
            return {
                value: this.val
            }
        },

        watch: {
            value: function(newVal, oldVal) {
                this.$emit('update', newVal);
            }
        }
    }
</script>
