<?php

return [

	'common' => [
		'comment' => '評論',
		'commented' => '已評論',
		'comments' => '條意見評論',
		'like' => '按讚',
		'liked' => '已按讚',
		'likes' => '個人已按讚',
		'share' => '分享',
		'shared' => '已分享',
		'shares' => '次分享',
		'unshare' => '取消分享',
		'bookmark' => '書籤',

		'cancel' => '取消',
		'copyLink' => '複製連結',
		'delete' => '删除',
		'error' => '錯誤',
		'errorMsg' => '出了點小問題，請稍後重試。',
		'oops' => '呃…',
		'other' => '其他',
		'readMore' => '繼續讀下去',
		'success' => '成功',
		'proceed' => '繼續',
		'next' => '下一個',
		'close' => '關閉',
		'clickHere' => '點擊此處',

		'sensitive' => '敏感內容',
		'sensitiveContent' => '敏感內容',
		'sensitiveContentWarning' => '這篇文可能包含敏感內容',
	],

	'site' => [
		'terms' => '使用條款',
		'privacy' => '隱私權政策',
	],

	'navmenu' => [
		'search' => '搜尋',
		'admin' => '管理儀表板',

		// Timelines
		'homeFeed' => '首頁動態',
		'localFeed' => '站內動態',
		'globalFeed' => '聯邦動態',

		// Core features
		'discover' => '探索',
		'directMessages' => '私人訊息',
		'notifications' => '通知',
		'groups' => '群組',
		'stories' => '限時動態',

		// Self links
		'profile' => '個人檔案',
		'drive' => '雲端硬碟',
		'settings' => '設定',
		'compose' => '新增',
		'logout' => '登出',

		// Nav footer
		'about' => '關於',
		'help' => '説明',
		'language' => '語言',
		'privacy' => '隱私權',
		'terms' => '條款',

		// Temporary links
		'backToPreviousDesign' => '回到先前的設計'
	],

	'directMessages' => [
		'inbox' => '收件夾',
		'sent' => '寄件夾',
		'requests' => '請求'
	],

	'notifications' => [
		'liked' => '喜歡你的',
		'commented' => '評論了你的',
		'reacted' => '反應了你的',
		'shared' => '分享了你的',
		'tagged' => 'tagged you in a',

		'updatedA' => 'updated a',
		'sentA' => 'sent a',

		'followed' => '已關注',
		'mentioned' => '被提及',
		'you' => '你',

		'yourApplication' => '您的加入申請',
		'applicationApproved' => '被批准了！',
		'applicationRejected' => '被拒絕。您可以在 6 個月後再次申請加入。',

		'dm' => '直接訊息',
		'groupPost' => 'group post',
		'modlog' => 'modlog',
		'post' => 'post',
		'story' => 'story',
		'noneFound' => 'No notifications found',
	],

	'post' => [
		'shareToFollowers' => 'Share to followers',
		'shareToOther' => 'Share to other',
		'noLikes' => 'No likes yet',
		'uploading' => '上傳中',
	],

	'profile' => [
		'posts' => 'Posts',
		'followers' => '跟隨者',
		'following' => '追蹤中',
		'admin' => '管理員',
		'collections' => 'Collections',
		'follow' => '追蹤',
		'unfollow' => '取消追蹤',
		'editProfile' => '編輯個人檔案',
		'followRequested' => '追蹤請求',
		'joined' => '已加入',

		'emptyCollections' => 'We can\'t seem to find any collections',
		'emptyPosts' => 'We can\'t seem to find any posts',
	],

	'menu' => [
		'viewPost' => 'View Post',
		'viewProfile' => 'View Profile',
		'moderationTools' => 'Moderation Tools',
		'report' => '檢舉',
		'archive' => '封存',
		'unarchive' => '取消封存',
		'embed' => '嵌入',

		'selectOneOption' => '選擇以下選項之一',
		'unlistFromTimelines' => '不在時間軸中上顯示',
		'addCW' => '增加內容警告',
		'removeCW' => '移除內容警告',
		'markAsSpammer' => '標記為垃圾訊息來源者',
		'markAsSpammerText' => '對計有及未來貼文設定成不列出和增加內容警告',
		'spam' => '垃圾訊息',
		'sensitive' => '敏感內容',
		'abusive' => '辱罵或有害',
		'underageAccount' => '未成年帳號',
		'copyrightInfringement' => '侵犯版權',
		'impersonation' => '假冒帳號',
		'scamOrFraud' => '詐騙內容',
		'confirmReport' => '確認檢舉',
		'confirmReportText' => '你確定要檢舉這篇貼文？',
		'reportSent' => '檢舉已送出！',
		'reportSentText' => '我們已經收到你的檢舉。',
		'reportSentError' => '檢舉此貼文時出現問題。',

		'modAddCWConfirm' => '您確定要為此貼文添加內容警告嗎？',
		'modCWSuccess' => '成功添加內容警告',
		'modRemoveCWConfirm' => '您確定要刪除此貼文上的內容警告嗎？',
		'modRemoveCWSuccess' => '已成功刪除內容警告',
		'modUnlistConfirm' => 'Are you sure you want to unlist this post?',
		'modUnlistSuccess' => 'Successfully unlisted post',
		'modMarkAsSpammerConfirm' => '您確定要將此使用者標記為垃圾訊息發送者嗎？所有現有和未來的貼文將改為非公開，並將加上內容警告。',
		'modMarkAsSpammerSuccess' => '已成功將帳戶標記為垃圾訊息發送者',

		'toFollowers' => '給關注者',

		'showCaption' => '顯示文字說明',
		'showLikes' => '顯示喜歡',
		'compactMode' => '緊湊模式',
		'embedConfirmText' => '使用此嵌入式內容即表示您同意我們的',

		'deletePostConfirm' => '你確定要刪除此貼文？',
		'archivePostConfirm' => '您確定要封存此貼文嗎？',
		'unarchivePostConfirm' => '您確定要解除封存此貼文嗎？',
	],

	'story' => [
		'add' => '新增故事'
	],

	'timeline' => [
		'peopleYouMayKnow' => '你可能認識',

		'onboarding' => [
			'welcome' => '歡迎',
			'thisIsYourHomeFeed' => 'This is your home feed, a chronological feed of posts from accounts you follow.',
			'letUsHelpYouFind' => 'Let us help you find some interesting people to follow',
			'refreshFeed' => '更新我的源',
		],
	],

	'hashtags' => [
		'emptyFeed' => '我們似乎找不到此主題標籤的任何貼文'
	],

	'report' => [
		'report' => '檢舉',
		'selectReason' => '選擇一個理由',
		'reported' => '已檢舉',
		'sendingReport' => '送出檢舉',
		'thanksMsg' => '感謝你的檢舉讓世界更美好！',
		'contactAdminMsg' => '如果你想要提供更多有關本次檢舉的內容給管理員',
	],

];
