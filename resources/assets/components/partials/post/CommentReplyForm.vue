<template>
    <div class="my-3">
    	<div class="d-flex align-items-top reply-form child-reply-form">
    		<img class="shadow-sm media-avatar border" :src="profile.avatar" width="40" height="40" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">

            <div style="display: flex;flex-grow: 1;position: relative;">
        		<textarea
        			class="form-control bg-light rounded-lg shadow-sm" style="resize: none;padding-right: 60px;"
        			placeholder="Write a comment...."
        			v-model="replyContent"
        			:disabled="isPostingReply" />

                <button
                    class="btn btn-sm py-1 font-weight-bold ml-1 rounded-pill"
                    :class="[replyContent && replyContent.length ? 'btn-primary' : 'btn-outline-muted']"
                    @click="storeComment"
                    :disabled="!replyContent || !replyContent.length"
                    style="position: absolute;right:10px;top:50%;transform:translateY(-50%)">
                    Post
                </button>
            </div>
    	</div>
        <p class="text-right small font-weight-bold text-lighter">{{ replyContent ? replyContent.length : 0 }}/{{ config.uploader.max_caption_length }}</p>
    </div>
</template>

<script type="text/javascript">
	export default {
		props: {
			parentId: {
				type: String
			}
		},

		data() {
			return {
                config: App.config,
				isPostingReply: false,
				replyContent: '',
				profile: window._sharedData.user,
				sensitive: false
			}
		},

		methods: {
			storeComment() {
				this.isPostingReply = true;

				axios.post('/api/v1/statuses', {
					status: this.replyContent,
					in_reply_to_id: this.parentId,
					sensitive: this.sensitive
				})
				.then(res => {
					this.replyContent = undefined;
					this.isPostingReply = false;
					this.$emit('new-comment', res.data);
					// this.ids.push(res.data.id);
					// this.feed.push(res.data);
				})
			},
		}
	}
</script>
