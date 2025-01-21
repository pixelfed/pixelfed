<?php

return [

	'common' => [
		'comment' => 'Kommentar',
		'commented' => 'Kommenterade',
		'comments' => 'Kommentarer',
		'like' => 'Gilla',
		'liked' => 'Gillad',
		'likes' => 'Gillanden',
		'share' => 'Dela',
		'shared' => 'Utdelad',
		'shares' => 'Utdelade',
		'unshare' => 'Sluta dela ut',
		'bookmark' => 'Bokmärk',

		'cancel' => 'Avbryt',
		'copyLink' => 'Kopiera länk',
		'delete' => 'Ta bort',
		'error' => 'Fel',
		'errorMsg' => 'Något gick fel. Vänligen försök igen senare.',
		'oops' => 'Hoppsan!',
		'other' => 'Andra',
		'readMore' => 'Läs mer',
		'success' => 'Lyckades',
		'proceed' => 'Fortsätt',
		'next' => 'Nästa',
		'close' => 'Stäng',
		'clickHere' => 'klicka här',

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
		'notifications' => 'Notifikationer',
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
		'applicationApproved' => 'blev godkänd!',
		'applicationRejected' => 'nekades. Du kan söka för att gå med igen om 6 månader.',

		'dm' => 'dm',
		'groupPost' => 'gruppinlägg',
		'modlog' => 'modlog',
		'post' => 'inlägg',
		'story' => 'historik',
		'noneFound' => 'Inga aviseringar hittades',
	],

	'post' => [
		'shareToFollowers' => 'Dela till följare',
		'shareToOther' => 'Dela till andra',
		'noLikes' => 'Inga gilla-markeringar än',
		'uploading' => 'Laddar upp',
	],

	'profile' => [
		'posts' => 'Inlägg',
		'followers' => 'Följare',
		'following' => 'Följer',
		'admin' => 'Administratör',
		'collections' => 'Kollektioner',
		'follow' => 'Följ',
		'unfollow' => 'Sluta följa',
		'editProfile' => 'Redigera profil',
		'followRequested' => 'Följförfrågan skickad',
		'joined' => 'Gick med',

		'emptyCollections' => 'Vi verkar inte hitta några kollektioner',
		'emptyPosts' => 'Vi verkar inte hitta några inlägg',
	],

	'menu' => [
		'viewPost' => 'Visa inlägg',
		'viewProfile' => 'Visa profil',
		'moderationTools' => 'Modereringsverktyg',
		'report' => 'Rapportera',
		'archive' => 'Arkivera',
		'unarchive' => 'Avarkivera',
		'embed' => 'Bädda in',

		'selectOneOption' => 'Välj ett av följande alternativ',
		'unlistFromTimelines' => 'Avlista från tidslinjer',
		'addCW' => 'Lägg till innehållsvarning',
		'removeCW' => 'Ta bort känslighetsvarning',
		'markAsSpammer' => 'Markera som spammare',
		'markAsSpammerText' => 'Avlista + känslighetsmarkera nuvarande och framtida inlägg',
		'spam' => 'Skräppost',
		'sensitive' => 'Känsligt innehåll',
		'abusive' => 'Missbruk eller skadligt',
		'underageAccount' => 'Minderårigt konto',
		'copyrightInfringement' => 'Upphovsrättsintrång',
		'impersonation' => 'Personimitation',
		'scamOrFraud' => 'Bedrägeri',
		'confirmReport' => 'Bekräfta anmälan',
		'confirmReportText' => 'Är du säker att du vill anmäla detta inlägg?',
		'reportSent' => 'Anmälan skickad!',
		'reportSentText' => 'Vi har fått emot din anmälan till detta inlägg.',
		'reportSentError' => 'Något gick fel när detta inlägget skulle anmälas.',

		'modAddCWConfirm' => 'Är du säker att du vill lägga till känslighetsvarning på detta inlägget?',
		'modCWSuccess' => 'Lade till känslighetsvarning',
		'modRemoveCWConfirm' => 'Är du säker att du vill ta bort känslighetsvarningen på detta inlägget?',
		'modRemoveCWSuccess' => 'Tog bort känsloghetsvarning',
		'modUnlistConfirm' => 'Är du säker att du vill avlista detta inlägget?',
		'modUnlistSuccess' => 'Avlistade inlägget',
		'modMarkAsSpammerConfirm' => 'Är du säker att du vill markera detta konto som spammare? Alla nuvarande och framtida inlägg kommer avlistas och en känslighetsvarning kommer läggas till på inläggen',
		'modMarkAsSpammerSuccess' => 'Markerade kontot som spammare',

		'toFollowers' => 'till följare',

		'showCaption' => 'Visa bildtext',
		'showLikes' => 'Visa gilla-markeringar',
		'compactMode' => 'Kompaktläge',
		'embedConfirmText' => 'Genom att använda denna inbäddning godkänner du vår',

		'deletePostConfirm' => 'Är du säker att du vill ta bort detta inlägg?',
		'archivePostConfirm' => 'Är du säker att du vill arkivera detta inlägg?',
		'unarchivePostConfirm' => 'Är du säker att du vill avarkivera detta inlägg?',
	],

	'story' => [
		'add' => 'Lägg till Story'
	],

	'timeline' => [
		'peopleYouMayKnow' => 'Personer du kanske känner',

		'onboarding' => [
			'welcome' => 'Välkommen',
			'thisIsYourHomeFeed' => 'Detta är ditt hem foder, ett kronologiskt flöde av inlägg från konton du följer.',
			'letUsHelpYouFind' => 'Låt oss hjälpa dig att hitta några intressanta människor att följa',
			'refreshFeed' => 'Uppdatera mitt flöde',
		],
	],

	'hashtags' => [
		'emptyFeed' => 'Vi kan inte hitta några inlägg med den hashtagen'
	],

	'report' => [
		'report' => 'Rapportera',
		'selectReason' => 'Välj en anledning',
		'reported' => 'Rapporterad',
		'sendingReport' => 'Skickar rapport',
		'thanksMsg' => 'Tack för rapporten, personer som gillar att du hjälper till att skydda vår gemenskap!',
		'contactAdminMsg' => 'Om du vill kontakta en administratör om detta inlägg eller rapport',
	],

];
