<template>
    <div class="px-md-5" style="background-color: #fff;">
        <img
            v-if="group.metadata && group.metadata.hasOwnProperty('header')"
            :src="group.metadata.header.url"
            class="header-image">
        <div v-else class="header-jumbotron"></div>
    </div>
</template>

<script>
    export default {
        props: {
            group: {
                type: Object
            }
        }
    }
</script>

<style lang="scss" scoped>
    .header-image {
        width: 100%;
        height: auto;
        object-fit: cover;
        max-height: 220px;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
        margin-top:-1px;
        border: 1px solid var(--light);
        margin-bottom: 0px;

        @media(min-width: 768px) {
            max-height: 420px;
        }
    }

    .header-jumbotron {
        background-color: #F3F4F6;
        height: 320px;
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
    }
</style>
