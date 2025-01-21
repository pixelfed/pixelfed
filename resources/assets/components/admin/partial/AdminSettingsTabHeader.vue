<template>
    <div>
        <div class="d-flex justify-content-between align-items-center">
            <div style="width:100px;"></div>
            <div>
                <h2 class="display-4 mb-0" style="font-weight: 800;">{{ title }}</h2>
            </div>
            <div>
                <button
                    class="btn btn-primary rounded-pill font-weight-bold px-5"
                    :disabled="isSaving || saved"
                    @click.prevent="save">
                    <template v-if="isSaving === true"><b-spinner small class="mx-2" /></template>
                    <template v-else>{{ buttonLabel }}</template>
                </button>
            </div>
        </div>
        <hr class="mt-3">
    </div>
</template>

<script>
    export default {
        props: {
            title: {
                type: String
            },
            saving: {
                type: Boolean
            },
            saved: {
                type: Boolean
            }
        },

        computed: {
            buttonLabel: {
                get() {
                    if(this.saved) {
                        return 'Saved';
                    }
                    if(this.saving) {
                        return 'Saving';
                    }

                    return 'Save';
                }
            },
            isSaving: {
                get() {
                    return this.saving;
                }
            }
        },

        methods: {
            save($event) {
                $event.currentTarget?.blur();
                this.$emit('save');
            }
        }
    }
</script>
