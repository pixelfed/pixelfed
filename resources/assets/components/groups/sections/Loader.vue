<template>
    <div class="w-100 h-100">
        <div v-if="!loaded" class="d-flex w-100 h-100 py-5 justify-content-center align-items-center">
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="text-center font-weight-bold mt-1">Loading...</p>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            loaded: {
                type: Boolean,
                default: false
            }
        }
    }
</script>
