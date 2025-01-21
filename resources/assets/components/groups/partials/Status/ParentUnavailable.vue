<template>
    <div class="card shadow-sm" style="border-radius: 18px !important;">
        <div class="card-body pb-0">
            <div class="card card-body border shadow-none mb-3" style="background-color: #E5E7EB;">
                <div class="media p-md-4">
                    <div class="mr-4 pt-2">
                        <i class="fas fa-lock fa-2x"></i>
                    </div>
                    <div class="media-body" style="max-width: 320px">
                        <p class="lead font-weight-bold mb-1">This content isn't available right now</p>
                        <p class="mb-0" style="font-size: 12px;letter-spacing:-0.3px;">When this happens, it's usually because the owner only shared it with a small group of people, changed who can see it, or it's been deleted.</p>
                    </div>
                </div>
            </div>
            <div>
                <comment-drawer
                    v-if="showCommentDrawer"
                    :permalink-mode="permalinkMode"
                    :permalink-status="childContext"
                    :status="status"
                    :profile="profile"
                    :group-id="groupId"
                    :can-reply="false" />
            </div>
        </div>
    </div>
</template>

<script>
    import CommentDrawer from '@/groups/partials/CommentDrawer.vue';

    export default {
        props: {
            showCommentDrawer: {
                type: Boolean,
            },
            permalinkMode: {
                type: Boolean,
            },
            childContext: {
                type: Object
            },
            status: {
                type: Object
            },
            profile: {
                type: Object
            },
            groupId: {
                type: String
            },
        },

        components: {
            "comment-drawer": CommentDrawer,
        }
    }
</script>
