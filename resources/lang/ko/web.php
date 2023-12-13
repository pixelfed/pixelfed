<?php

return [

	'common' => [
		'comment' => '댓글',
		'commented' => '댓글 씀',
		'comments' => '댓글',
		'like' => '좋아요',
		'liked' => '좋아함',
		'likes' => '좋아요',
		'share' => '공유',
		'shared' => '공유함',
		'shares' => '공유하기',
		'unshare' => '공유 해제',
		'bookmark' => '북마크',

		'cancel' => '취소',
		'copyLink' => '링크 복사',
		'delete' => '지우기',
		'error' => '오류',
		'errorMsg' => '문제가 발생했습니다. 나중에 다시 시도하세요.',
		'oops' => '이런!',
		'other' => '등',
		'readMore' => '더보기',
		'success' => '성공',
		'proceed' => '진행',
		'next' => '다음',
		'close' => '닫기',
		'clickHere' => '여기를 클릭',

		'sensitive' => '민감함 ',
		'sensitiveContent' => '민감한 내용',
		'sensitiveContentWarning' => '이 게시물은 민감한 내용을 포함합니다',
	],

	'site' => [
		'terms' => '이용약관',
		'privacy' => '개인정보 취급방침',
	],

	'navmenu' => [
		'search' => '검색',
		'admin' => '관리자 대시보드',

		// Timelines
		'homeFeed' => '홈 피드',
		'localFeed' => '로컬 피드',
		'globalFeed' => '글로벌 피드',

		// Core features
		'discover' => '발견',
		'directMessages' => '쪽지',
		'notifications' => '알림',
		'groups' => '그룹',
		'stories' => '이야기',

		// Self links
		'profile' => '프로필',
		'drive' => '드라이브',
		'settings' => '설정',
		'compose' => '새로 만들기',
		'logout' => '로그아웃',

		// Nav footer
		'about' => '정보',
		'help' => '도움말',
		'language' => '언어',
		'privacy' => '개인정보',
		'terms' => '약관',

		// Temporary links
		'backToPreviousDesign' => '이전 디자인으로 되돌리기'
	],

	'directMessages' => [
		'inbox' => '받은쪽지함',
		'sent' => '보냄',
		'requests' => '요청 내역'
	],

	'notifications' => [
		'liked' => '내가 좋아한',
		'commented' => '내 댓글',
		'reacted' => '내 반응',
		'shared' => '내가 공유함',
		'tagged' => '내가 태그됨',

		'updatedA' => '업데이트',
		'sentA' => '보낸 이',

		'followed' => '님이 팔로우',
		'mentioned' => ' 님으로부터의 멘션',
		'you' => '나',

		'yourApplication' => '가입 신청서 작성',
		'applicationApproved' => '승인되었습니다!',
		'applicationRejected' => '이 반려되었습니다. 6개월 후에 다시 재신청할 수 있습니다.',

		'dm' => 'DM',
		'groupPost' => '묶어 발행',
		'modlog' => '모드로그',
		'post' => '발행',
		'story' => '이야기',
		'noneFound' => '알림을 찾을 수 없음',
	],

	'post' => [
		'shareToFollowers' => '팔로워에게 공유하기',
		'shareToOther' => '다른 곳에 공유하기',
		'noLikes' => '아직 좋아요 없음',
		'uploading' => '업로드 중',
	],

	'profile' => [
		'posts' => '발행물',
		'followers' => '팔로워',
		'following' => '팔로잉',
		'admin' => '관리자',
		'collections' => '컬렉션',
		'follow' => '팔로우',
		'unfollow' => '언팔로우',
		'editProfile' => '프로필 편집',
		'followRequested' => '팔로우 요청함',
		'joined' => '가입함',

		'emptyCollections' => '아무 컬렉션도 보이지 않습니다.',
		'emptyPosts' => '아무 발행물도 보이지 않습니다.',
	],

	'menu' => [
		'viewPost' => '발행물 보기',
		'viewProfile' => '프로필 보기',
		'moderationTools' => '중재 도구',
		'report' => '신고',
		'archive' => '보관',
		'unarchive' => '보관 해제',
		'embed' => '임베드',

		'selectOneOption' => '다음의 선택사항 중 하나를 고르세요.',
		'unlistFromTimelines' => '타임라인에서 제외',
		'addCW' => '내용 경고 붙이기',
		'removeCW' => '내용 경고 떼기',
		'markAsSpammer' => '스패머로 표시',
		'markAsSpammerText' => '현존 및 미래 발행물에 미등재 및 내용 경고',
		'spam' => '스팸',
		'sensitive' => '민감한 내용',
		'abusive' => '가학 또는 유해',
		'underageAccount' => '미성년 계정',
		'copyrightInfringement' => '저작권 위반',
		'impersonation' => '사칭',
		'scamOrFraud' => '스팸 또는 사기',
		'confirmReport' => '신고 확인',
		'confirmReportText' => '이 게시물을 제보할까요?',
		'reportSent' => '신고 발송!',
		'reportSentText' => '제보를 잘 수령하였습니다.',
		'reportSentError' => 'There was an issue reporting this post.',

		'modAddCWConfirm' => '이 게시물이 내용 경고를 붙일까요?',
		'modCWSuccess' => '내용경고를 붙임',
		'modRemoveCWConfirm' => 'Are you sure you want to remove the content warning on this post?',
		'modRemoveCWSuccess' => '내용경고를 뗌',
		'modUnlistConfirm' => 'Are you sure you want to unlist this post?',
		'modUnlistSuccess' => '발행물 미등재 처리를 마쳤습니다.',
		'modMarkAsSpammerConfirm' => '해당 이용자를 정말 스패머로 표시할까요?
내용 경고가 적용되며 이전과 이후의 모든 발행물이 타임라인에 미등재됩니다.',
		'modMarkAsSpammerSuccess' => '스패머 계정으로 표시함',

		'toFollowers' => '팔로워',

		'showCaption' => '자막 보이기',
		'showLikes' => '좋아요 보기',
		'compactMode' => '콤팩트 모드',
		'embedConfirmText' => 'By using this embed, you agree to our',

		'deletePostConfirm' => 'Are you sure you want to delete this post?',
		'archivePostConfirm' => '이 발행물을 정말 보관할까요?',
		'unarchivePostConfirm' => '이 발행물을 정말 보관 취소할까요?',
	],

	'story' => [
		'add' => '이야기 추가'
	],

	'timeline' => [
		'peopleYouMayKnow' => '알 수도 있는 사람',

		'onboarding' => [
			'welcome' => '반가워요',
			'thisIsYourHomeFeed' => '이곳은 팔로우 한 게시물을 시간 순으로 보여주는 홈 피드예요.',
			'letUsHelpYouFind' => 'Let us help you find some interesting people to follow',
			'refreshFeed' => '내 피드 새로 고침',
		],
	],

	'hashtags' => [
		'emptyFeed' => 'We can\'t seem to find any posts for this hashtag'
	],

	'report' => [
		'report' => '신고',
		'selectReason' => '이유 고르기',
		'reported' => '신고 마침',
		'sendingReport' => '신고 보내는 중',
		'thanksMsg' => 'Thanks for the report, people like you help keep our community safe!',
		'contactAdminMsg' => 'If you\'d like to contact an administrator about this post or report',
	],

];
