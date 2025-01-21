<template>
    <div class="card shadow-none border card-body">
        <div class="form-group mb-0">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" :name="elementId" class="custom-control-input" :id="elementId" :checked="value" @change="$emit('change', !value)">
                <label class="custom-control-label font-weight-bold" :for="elementId">{{ name }}</label>
            </div>
            <p class="mt-1 mb-0 small text-muted" v-html="description"></p>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            name: {
                type: String
            },

            value: {
                type: Boolean
            },

            description: {
                type: String
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
