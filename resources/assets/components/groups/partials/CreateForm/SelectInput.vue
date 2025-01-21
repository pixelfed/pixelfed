<template>
    <div class="form-group row">
        <div class="col-sm-3">
            <label class="col-form-label text-left">{{ label }}</label>
        </div>
        <div class="col-sm-9">
            <select class="custom-select" v-model="value">
                <option value="" selected="" disabled="">{{ placeholder }}</option>
                <option v-for="c in categories" :value="c.value">{{ c.key }}</option>
            </select>

            <div
                v-if="helpText || hasLimit"
                class="help-text small text-muted d-flex flex-row justify-content-between gap-3">
                <div v-if="helpText">{{ helpText }}</div>
                <div
                    v-if="hasLimit"
                    class="font-weight-bold text-dark">
                    {{ value ? value.length : 0 }}/{{ maxLimit }}
                </div>
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
            placeholder: {
                type: String
            },
            categories: {
                type: Array
            },
            val: {
                type: String
            },
            helpText: {
                type: String
            },
            hasLimit: {
                type: Boolean,
                default: false
            },
            maxLimit: {
                type: Number,
                default: 40
            },
            largeInput: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                value: this.val ? this.val : ""
            }
        },

        watch: {
            value: function(newVal, oldVal) {
                this.$emit('update', newVal);
            }
        }
    }
</script>
