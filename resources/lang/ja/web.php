<?php

return [

	'common' => [
		'comment' => 'コメント',
		'commented' => 'コメントされました',
		'comments' => 'コメント',
		'like' => 'お気に入り',
		'liked' => 'お気に入りしました',
		'likes' => 'お気に入り',
		'share' => '共有',
		'shared' => '共有されました',
		'shares' => '共有',
		'unshare' => '共有解除',

		'cancel' => 'キャンセル',
		'copyLink' => 'リンクをコピー',
		'delete' => '削除',
		'error' => 'エラー',
		'errorMsg' => '何かが間違っています。しばらくしてからやり直してください。',
		'oops' => 'おおっと！',
		'other' => 'その他',
		'readMore' => 'もっと読む',
		'success' => '成功しました',

		'sensitive' => 'センシティブ',
		'sensitiveContent' => 'センシティブなコンテンツ',
		'sensitiveContentWarning' => 'この投稿にはセンシティブなコンテンツが含まれている可能性があります',
	],

	'site' => [
		'terms' => '利用規約',
		'privacy' => 'プライバシーポリシー',
	],

	'navmenu' => [
		'search' => '検索',
		'admin' => '管理者ダッシュボード',

		// Timelines
		'homeFeed' => 'ホームフィード',
		'localFeed' => 'ローカルフィード',
		'globalFeed' => 'グローバルフィード',

		// Core features
		'discover' => '発見',
		'directMessages' => 'ダイレクトメッセージ',
		'notifications' => '通知',
		'groups' => 'グループ',
		'stories' => 'ストーリーズ',

		// Self links
		'profile' => 'プロフィール',
		'drive' => 'ドライブ',
		'settings' => '設定',
		'compose' => '新規投稿',

		// Nav footer
		'about' => 'このサーバーについて',
		'help' => 'ヘルプ',
		'language' => '言語',
		'privacy' => 'プライバシー',
		'terms' => '利用規約',

		// Temporary links
		'backToPreviousDesign' => '以前のデザインに戻す'
	],

	'directMessages' => [
		'inbox' => '受信トレイ',
		'sent' => '送信済み',
		'requests' => 'リクエスト'
	],

	'notifications' => [
		'liked' => 'liked your',
		'commented' => 'commented on your',
		'reacted' => 'reacted to your',
		'shared' => 'shared your',
		'tagged' => 'tagged you in a',

		'updatedA' => 'updated a',
		'sentA' => 'sent a',

		'followed' => 'followed',
		'mentioned' => 'mentioned',
		'you' => 'あなた',

		'yourApplication' => 'Your application to join',
		'applicationApproved' => 'was approved!',
		'applicationRejected' => 'was rejected. You can re-apply to join in 6 months.',

		'dm' => 'dm',
		'groupPost' => 'group post',
		'modlog' => 'モデレーションログ',
		'post' => '投稿',
		'story' => 'ストーリー',
	],

	'post' => [
		'shareToFollowers' => 'フォロワーに共有',
		'shareToOther' => 'その他に共有',
		'noLikes' => 'まだお気に入りされていません',
		'uploading' => 'アップロード中',
	],

	'profile' => [
		'posts' => '投稿',
		'followers' => 'フォロワー',
		'following' => 'フォロー中',
		'admin' => '管理者',
		'collections' => 'コレクション',
		'follow' => 'フォロー',
		'unfollow' => 'フォロー解除',
		'editProfile' => 'プロフィールを編集',
		'followRequested' => 'フォロー承認待ち',
		'joined' => '参加しました',
	],

	'menu' => [
		'viewPost' => '投稿を見る',
		'viewProfile' => 'プロフィールを見る',
		'moderationTools' => 'モデレーションツール',
		'report' => '報告',
		'archive' => 'アーカイブ',
		'unarchive' => 'アーカイブを解除',
		'embed' => 'Embed',

		'selectOneOption' => 'Select one of the following options',
		'unlistFromTimelines' => 'Unlist from Timelines',
		'addCW' => 'Add Content Warning',
		'removeCW' => 'Remove Content Warning',
		'markAsSpammer' => 'スパムとしてマーク',
		'markAsSpammerText' => 'Unlist + CW existing and future posts',
		'spam' => 'スパム',
		'sensitive' => 'センシティブなコンテンツ',
		'abusive' => '虐待または有害',
		'underageAccount' => '未成年のアカウント',
		'copyrightInfringement' => '著作権侵害',
		'impersonation' => 'なりすまし',
		'scamOrFraud' => '詐欺または不正な行為',
		'confirmReport' => '報告を送信',
		'confirmReportText' => '本当にこの投稿を報告しますか？',
		'reportSent' => '報告が送信されました！',
		'reportSentText' => 'あなたの報告を受け取りました。',
		'reportSentError' => 'There was an issue reporting this post.',

		'modAddCWConfirm' => 'この投稿にコンテンツ警告を追加してもよろしいですか？',
		'modCWSuccess' => 'コンテンツ警告が追加されました',
		'modRemoveCWConfirm' => '本当にこの投稿からコンテンツ警告を削除しますか？',
		'modRemoveCWSuccess' => 'コンテンツ警告が削除されました',
		'modUnlistConfirm' => 'Are you sure you want to unlist this post?',
		'modUnlistSuccess' => 'Successfully unlisted post',
		'modMarkAsSpammerConfirm' => 'このユーザーをスパムとして登録していいですか？既存のまた、今後の投稿はすべてタイムラインに表示されず、コンテンツ警告が適用されます。',
		'modMarkAsSpammerSuccess' => 'アカウントをスパムとしてマークしました',

		'toFollowers' => 'to Followers',

		'showCaption' => 'Show Caption',
		'showLikes' => 'Show Likes',
		'compactMode' => 'Compact Mode',
		'embedConfirmText' => 'By using this embed, you agree to our',

		'deletePostConfirm' => 'Are you sure you want to delete this post?',
		'archivePostConfirm' => 'Are you sure you want to archive this post?',
		'unarchivePostConfirm' => 'Are you sure you want to unarchive this post?',
	],

	'story' => [
		'add' => 'ストーリーを追加'
	],

	'timeline' => [
		'peopleYouMayKnow' => '知り合いかも'
	]

];
