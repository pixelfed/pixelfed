<template>
	<div class="web-wrapper">
		<div class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-3 d-md-block">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6">
					<div class="jumbotron shadow-sm bg-white">
						<div class="text-center">
							<h1 class="font-weight-bold">What's New</h1>
							<p class="lead mb-0">A log of changes in our new UI (Metro 2.0)</p>
						</div>
					</div>

					<div class="mt-4 pb-3">
						<p class="lead">Apr 3, 2022</p>
						<ul>
							<li class="font-weight-bold">
								Dark Mode <br />
								<p class="small">To enable dark or light mode, click on the top nav menu and then select UI Settings</p>
							</li>
							<li>Improved internationalization support with the addition of 7 new languages</li>
						</ul>
					</div>

					<div class="mt-4 pb-3">
						<p class="lead">March 2022</p>
						<ul>
							<li class="font-weight-bold">Full screen previews on photo albums</li>
							<li class="font-weight-bold">Filter notifications by type on notifications tab</li>
							<li>Add "Shared by" link to posts that opens a list of accounts that reblogged the post</li>
							<li>Fix private profile feed not loading for owner</li>
						</ul>
					</div>

					<div class="mt-4 pb-3">
						<p class="lead">Febuary 2022</p>
						<ul>
							<li class="font-weight-bold">New Discover layout with My Hashtags, My Memories, Account Insights, Find Friends and Server Timelines</li>
							<li class="font-weight-bold">Mobile app drawer menu</li>
							<li class="font-weight-bold">Add Preferred Profile Layout UI setting</li>
							<li>Add search bar to mobile breakpoints and adjust avatar size when necessary</li>
							<li>Improved profile layout on mobile breakpoints</li>
							<li class="font-weight-bold">Threaded Comments Beta</li>
							<li>Changed default media preview setting to non-fixed height media</li>
							<li>Improved Compose Location search, will display popular locations first</li>
							<li>Added "Comment" button to comment reply form and changed textarea/multiline icon toggle</li>
							<li>Fixed incorrect avatar in profile post comment drawers</li>
							<li>Hashtags will now include remote/federated posts on hashtag feeds</li>
							<li>Moved media license to post header</li>
							<li class="font-weight-bold">Improved Media Previews - disable to restore original preview aspect ratios</li>
							<li class="font-weight-bold">Comments + Hovercards - comment usernames now support hovercards</li>
							<li class="font-weight-bold">Quick Avatar Updates - click the <i class="far fa-cog"></i> button on your avatar. Supports drag-n-drop and updates without page refresh in most places.</li>
							<li class="font-weight-bold">Follows You badge on hovercards when applicable</li>
							<li>Add Hide Counts & Stats setting</li>
							<li>Fix nsfw videos not displaying sensitive warning</li>
							<li>Added mod tools button to posts for admin accounts</li>
						</ul>
					</div>

					<div class="mt-4 pb-3">
						<p class="lead">January 2022</p>
						<ul>
							<li>Fixed comment deletion</li>
							<li class="font-weight-bold">New Reaction bar on posts - to disable toggle this option in UI Settings menu</li>
							<li class="font-weight-bold">Fresher Comments - fixed bug that prevented new comments from appearing and added a Refresh button to do it manually in the event it's needed</li>
							<li>Added Comment Autoloading setting, enabled by default, it loads the first 3 comments</li>
							<li>Added Likes feed to your own profile to view posts you liked</li>
							<li>Fixed custom emoji rendering on sidebar, profiles and hover cards</li>
							<li>Fixed duplicate notifications on feeds</li>
							<li>New onboarding home feed with 6 popular accounts to help newcomers find accounts to follow</li>
							<li>Added pronouns to hovercards</li>
							<li>Fixed custom emoji rendering on home timeline</li>
							<li class="font-weight-bold">Added Hovercards to usernames on timelines</li>
							<li class="font-weight-bold">Improved search bar, now resolves (and imports) remote accounts and posts, including webfinger addresses</li>
							<li>Added full account usernames to notifications on hover</li>
							<li>Discover page will default to the Yearly tab if Daily tab is empty</li>
							<li>Hashtag feed improvements (fix pagination placeholders, use 4x4 grid on larger screens)</li>
							<li class="font-weight-bold">New report modal for posts & comments</li>
							<li>Fixed profile <i class="far fa-bars px-1"></i> feed status interactions</li>
							<li>Improved profile mobile layout</li>
						</ul>
					</div>

					<div class="pb-3">
						<p class="lead">Older</p>
						<p>To view the full list of changes, view the project changelog <a href="https://raw.githubusercontent.com/pixelfed/pixelfed/dev/CHANGELOG.md">here</a>.</p>
					</div>
				</div>

				<div class="col-md-3">
					<div class="alert alert-primary">
						<p class="mb-0 small">We know some of our UI changes are controversial. We value your feedback and are working hard to incorporate it. Your patience is greatly appreciated!</p>
					</div>
				</div>
			</div>
		</div>
		<drawer />
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';

	export default {
		components: {
			"drawer": Drawer,
			"sidebar": Sidebar,
		},

		data() {
			return {
				isLoaded: false,
				profile: undefined,
				instance: undefined,
				nodeinfo: undefined,
				config: window.App.config
			}
		},

		mounted() {
			this.profile = window._sharedData.user;
		}
	}
</script>
