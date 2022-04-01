<?php

return [

	'common' => [
		'comment' => 'Kommentar',
		'commented' => 'Kommenterades',
		'comments' => 'Kommentarer',
		'like' => 'Gilla',
		'liked' => 'Gillad',
		'likes' => 'Gillanden',
		'share' => 'Dela',
		'shared' => 'Utdelad',
		'shares' => 'Utdelade',
		'unshare' => 'Sluta dela ut',
		'bookmark' => 'Bookmark',

		'cancel' => 'Avbryt',
		'copyLink' => 'Kopiera länk',
		'delete' => 'Ta bort',
		'error' => 'Fel',
		'errorMsg' => 'Något gick fel. Vänligen försök igen senare.',
		'oops' => 'Hoppsan!',
		'other' => 'Annat',
		'readMore' => 'Läs mer',
		'success' => 'Lyckades',
		'proceed' => 'Proceed',
		'next' => 'Next',
		'close' => 'Close',
		'clickHere' => 'click here',

		'sensitive' => 'Känsligt',
		'sensitiveContent' => 'Känsligt innehåll',
		'sensitiveContentWarning' => 'Det här inlägget kan innehålla känsligt innehåll',
	],

	'site' => [
		'terms' => 'Användarvillkor',
		'privacy' => 'Integritetspolicy',
	],

	'navmenu' => [
		'search' => 'Sök',
		'admin' => 'Kontrollpanel för administratör',

		// Timelines
		'homeFeed' => 'Hemflöde',
		'localFeed' => 'Lokalt flöde',
		'globalFeed' => 'Globalt flöde',

		// Core features
		'discover' => 'Upptäck',
		'directMessages' => 'Direktmeddelanden',
		'notifications' => 'Aviseringar',
		'groups' => 'Grupper',
		'stories' => 'Historier',

		// Self links
		'profile' => 'Profil',
		'drive' => 'Enhet',
		'settings' => 'Inställningar',
		'compose' => 'Skapa ny',
		'logout' => 'Logga ut',

		// Nav footer
		'about' => 'Om',
		'help' => 'Hjälp',
		'language' => 'Språk',
		'privacy' => 'Integritet',
		'terms' => 'Villkor',

		// Temporary links
		'backToPreviousDesign' => 'Gå tillbaka till föregående design'
	],

	'directMessages' => [
		'inbox' => 'Inkorg',
		'sent' => 'Skickat',
		'requests' => 'Förfrågningar'
	],

	'notifications' => [
		'liked' => 'gillade ditt',
		'commented' => 'kommenterade på din',
		'reacted' => 'reagerade på din',
		'shared' => 'delade din',
		'tagged' => 'taggade dig i ett',

		'updatedA' => 'uppdaterade en',
		'sentA' => 'skickade en',

		'followed' => 'följde',
		'mentioned' => 'nämnde',
		'you' => 'du',

		'yourApplication' => 'Din ansökan för att gå med',
		'applicationApproved' => 'godkändes!',
		'applicationRejected' => 'nekades. Du kan söka för att gå med igen om 6 månader.',

		'dm' => 'dm',
		'groupPost' => 'gruppinlägg',
		'modlog' => 'modlog',
		'post' => 'inlägg',
		'story' => 'historik',
		'noneFound' => 'No notifications found',
	],

	'post' => [
		'shareToFollowers' => 'Dela till följare',
		'shareToOther' => 'Dela till andra',
		'noLikes' => 'Inga gillningar än',
		'uploading' => 'Laddar upp',
	],

	'profile' => [
		'posts' => 'Inlägg',
		'followers' => 'Följare',
		'following' => 'Följer',
		'admin' => 'Administratör',
		'collections' => 'Samlingar',
		'follow' => 'Följ',
		'unfollow' => 'Sluta följa',
		'editProfile' => 'Redigera profil',
		'followRequested' => 'Follow Requested',
		'joined' => 'Gick med',

		'emptyCollections' => 'Vi verkar inte kunna hitta några samlingar',
		'emptyPosts' => 'Vi verkar inte riktigt kunna hitta några inlägg',
	],

	'menu' => [
		'viewPost' => 'Visa inlägg',
		'viewProfile' => 'Visa profil',
		'moderationTools' => 'Verktyg för moderering',
		'report' => 'Rapportera',
		'archive' => 'Arkivera',
		'unarchive' => 'Avarkivera',
		'embed' => 'Bädda in',

		'selectOneOption' => 'Välj något av följande alternativ',
		'unlistFromTimelines' => 'Unlist from Timelines',
		'addCW' => 'Lägg till innehållsvarning',
		'removeCW' => 'Ta bort innehållsvarning',
		'markAsSpammer' => 'Markera som skickare av skräppost',
		'markAsSpammerText' => 'Unlist + CW existing and future posts',
		'spam' => 'Skräppost',
		'sensitive' => 'Känsligt innehåll',
		'abusive' => 'Abusive or Harmful',
		'underageAccount' => 'Minderårigt konto',
		'copyrightInfringement' => 'Upphovsrättsintrång',
		'impersonation' => 'Imitering',
		'scamOrFraud' => 'Lurendrejeri eller Bedrägeri',
		'confirmReport' => 'Bekräfta rapport',
		'confirmReportText' => 'Är du säker på att du vill rapportera det här inlägget?',
		'reportSent' => 'Rapporten skickades!',
		'reportSentText' => 'Mottagningen av din rapport lyckades.',
		'reportSentError' => 'Det uppstod ett problem när det här inlägget skulle rapporteras.',

		'modAddCWConfirm' => 'Are you sure you want to add a content warning to this post?',
		'modCWSuccess' => 'Successfully added content warning',
		'modRemoveCWConfirm' => 'Are you sure you want to remove the content warning on this post?',
		'modRemoveCWSuccess' => 'Successfully removed content warning',
		'modUnlistConfirm' => 'Are you sure you want to unlist this post?',
		'modUnlistSuccess' => 'Successfully unlisted post',
		'modMarkAsSpammerConfirm' => 'Are you sure you want to mark this user as a spammer? All existing and future posts will be unlisted on timelines and a content warning will be applied.',
		'modMarkAsSpammerSuccess' => 'Successfully marked account as spammer',

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
		'add' => 'Add Story'
	],

	'timeline' => [
		'peopleYouMayKnow' => 'People you may know',

		'onboarding' => [
			'welcome' => 'Welcome',
			'thisIsYourHomeFeed' => 'This is your home feed, a chronological feed of posts from accounts you follow.',
			'letUsHelpYouFind' => 'Let us help you find some interesting people to follow',
			'refreshFeed' => 'Refresh my feed',
		],
	],

	'hashtags' => [
		'emptyFeed' => 'We can\'t seem to find any posts for this hashtag'
	],

	'report' => [
		'report' => 'Report',
		'selectReason' => 'Select a reason',
		'reported' => 'Reported',
		'sendingReport' => 'Sending report',
		'thanksMsg' => 'Thanks for the report, people like you help keep our community safe!',
		'contactAdminMsg' => 'If you\'d like to contact an administrator about this post or report',
	],

];
