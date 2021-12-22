<?php

return [

	'common' => [
		'comment' => 'Comment',
		'commented' => 'Kommentiert',
		'comments' => 'Kommentare',
		'like' => 'Like',
		'liked' => 'Liked',
		'likes' => 'Likes',
		'share' => 'Teilen',
		'shared' => 'Geteilt',
		'shares' => 'Shares',
		'unshare' => 'Teilen rückgängig',

		'cancel' => 'Abbrechen',
		'copyLink' => 'Link kopieren',
		'delete' => 'Delete',
		'error' => 'Fehler',
		'errorMsg' => 'Etwas ist schief gelaufen. Bitter versuch es später nochmal.',
		'oops' => 'Hoppla!',
		'other' => 'Other',
		'readMore' => 'Mehr lesen',
		'success' => 'Erfolgreich',

		'sensitive' => 'Sensibel',
		'sensitiveContent' => 'Sensibler Inhalt',
		'sensitiveContentWarning' => 'Dieser Beitrag kann sensible Inhalte enthalten',
	],

	'site' => [
		'terms' => 'Nutzungsbedingungen',
		'privacy' => 'Datenschutzrichtlinien',
	],

	'navmenu' => [
		'search' => 'Suche',
		'admin' => 'Admin Dashboard',

		// Timelines
		'homeFeed' => 'Startseite',
		'localFeed' => 'Lokaler Feed',
		'globalFeed' => 'Globaler Feed',

		// Core features
		'discover' => 'Entdecken',
		'directMessages' => 'Direktnachrichten',
		'notifications' => 'Benachrichtigungen',
		'groups' => 'Gruppen',
		'stories' => 'Storys',

		// Self links
		'profile' => 'Profil',
		'drive' => 'Drive',
		'settings' => 'Einstellungen',
		'compose' => 'Neu erstellen',

		// Temporary links
		'backToPreviousDesign' => 'Go back to previous design'
	],

	'directMessages' => [
		'inbox' => 'Posteingang',
		'sent' => 'Gesendet',
		'requests' => 'Anfragen'
	],

	'notifications' => [
		'liked' => 'gefällt dein',
		'commented' => 'kommentierte dein',
		'reacted' => 'reagierte auf dein',
		'shared' => 'teilte deine',
		'tagged' => 'markierte dich in einem',

		'updatedA' => 'aktualisierte ein',
		'sentA' => 'sendete ein',

		'followed' => 'followed',
		'mentioned' => 'erwähnt',
		'you' => 'du',

		'yourApplication' => 'Deine Bewerbung um beizutreten',
		'applicationApproved' => 'wurde genehmigt!',
		'applicationRejected' => 'wurde abgelehnt. Du kannst dich in 6 Monaten erneut für den Beitritt bewerben.',

		'dm' => 'dm',
		'groupPost' => 'group post',
		'modlog' => 'modlog',
		'post' => 'post',
		'story' => 'story',
	],

	'post' => [
		'shareToFollowers' => 'Share to followers',
		'shareToOther' => 'Share to other',
		'noLikes' => 'No likes yet',
		'uploading' => 'Lädt hoch',
	],

	'profile' => [
		'posts' => 'Beiträge',
		'followers' => 'Folgende',
		'following' => 'Folgend',
		'admin' => 'Admin',
		'collections' => 'Sammlungen',
		'follow' => 'Folgen',
		'unfollow' => 'Entfolgen',
		'editProfile' => 'Profil bearbeiten',
		'followRequested' => 'Folgeanfragen',
		'joined' => 'Beigetreten',
	],

	'menu' => [
		'viewPost' => 'Beitrag anzeigen',
		'viewProfile' => 'Profil anzeigen',
		'moderationTools' => 'Moderationswerkzeuge',
		'report' => 'Melden',
		'archive' => 'Archiv',
		'unarchive' => 'Unarchive',
		'embed' => 'Einbetten',

		'selectOneOption' => 'Wähle eine der folgenden Möglichkeiten',
		'unlistFromTimelines' => 'Nicht in Timelines listen',
		'addCW' => 'Inhaltswarnung hinzufügen',
		'removeCW' => 'Inhaltswarnung entfernen',
		'markAsSpammer' => 'Als Spammer markieren',
		'markAsSpammerText' => 'Unlist + CW existing and future posts',
		'spam' => 'Spam',
		'sensitive' => 'Sensitiver Inhalt',
		'abusive' => 'Abusive or Harmful',
		'underageAccount' => 'Underage Account',
		'copyrightInfringement' => 'Urheberrechtsverletzung',
		'impersonation' => 'Impersonation',
		'scamOrFraud' => 'Scam or Fraud',
		'confirmReport' => 'Meldung bestätigen',
		'confirmReportText' => 'Bist du sicher, dass du diesen Beitrag melden möchtest?',
		'reportSent' => 'Meldung gesendet!',
		'reportSentText' => 'Wir haben deinen Bericht erfolgreich erhalten.',
		'reportSentError' => 'Es gab ein Problem beim Melden dieses Beitrags.',

		'modAddCWConfirm' => 'Are you sure you want to add a content warning to this post?',
		'modCWSuccess' => 'Inhaltswarnung erfolgreich hinzugefügt',
		'modRemoveCWConfirm' => 'Bist du sicher, dass die Inhaltswarnung auf diesem Beitrag entfernt werden soll?',
		'modRemoveCWSuccess' => 'Inhaltswarnung erfolgreich entfernt',
		'modUnlistConfirm' => 'Bist du sicher, dass du diesen Beitrag nicht listen möchtest?',
		'modUnlistSuccess' => 'Beitrag erfolgreich nicht gelistet',
		'modMarkAsSpammerConfirm' => 'Are you sure you want to mark this user as a spammer? All existing and future posts will be unlisted on timelines and a content warning will be applied.',
		'modMarkAsSpammerSuccess' => 'Konto erfolgreich als Spammer markiert',

		'toFollowers' => 'to Followers',

		'showCaption' => 'Bildunterschrift anzeigen',
		'showLikes' => 'Show Likes',
		'compactMode' => 'Kompaktmodus',
		'embedConfirmText' => 'Mit der Nutzung dieser Einbettung erklärst du dich mit unseren',

		'deletePostConfirm' => 'Bist du sicher, dass du diesen Beitrag löschen möchtest?',
		'archivePostConfirm' => 'Bist du sicher, dass du diesen Beitrag archivieren möchtest?',
		'unarchivePostConfirm' => 'Are you sure you want to unarchive this post?',
	],

	'story' => [
		'add' => 'Story hinzufügen'
	],

	'timeline' => [
		'peopleYouMayKnow' => 'Leute, die du vielleicht kennst'
	]

];
