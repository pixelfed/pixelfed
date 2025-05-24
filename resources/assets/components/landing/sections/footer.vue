<template>
    <div class="footer-component">
        <div class="footer-component-links">
            <a href="/site/help">Help</a>
            <div class="spacer">·</div>
            <a href="/site/terms">Terms</a>
            <div class="spacer">·</div>
            <a href="/site/privacy">Privacy</a>
            <div class="spacer">·</div>
            <a v-if="config.show_legal_notice_link" href="/site/legal-notice">Legal Notice</a>
            <div v-if="config.show_legal_notice_link" class="spacer">·</div>
            <a href="https://pixelfed.org/mobile-apps" target="_blank">Mobile Apps</a>
        </div>

        <div class="footer-component-attribution">
            <div><span>© {{ getYear() }} {{ config.domain }}</span></div>
            <div class="spacer">·</div>
            <div><a href="https://pixelfed.org" class="text-bluegray-500 font-weight-bold">Powered by Pixelfed</a></div>
            <div class="spacer">·</div>
            <div><span>v{{ config.version }}</span></div>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    data() {
        return {
            config: window.pfl
        }
    },

    methods: {
        getYear() {
            return (new Date().getFullYear());
        }
    }
}
</script>
