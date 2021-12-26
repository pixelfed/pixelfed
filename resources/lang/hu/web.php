<?php

return [

	'common' => [
		'comment' => 'Hozzászólás',
		'commented' => 'Hozzászólva',
		'comments' => 'Hozzászólások',
		'like' => 'Tetszik',
		'liked' => 'Tetszik',
		'likes' => 'Kedvelések',
		'share' => 'Megosztás',
		'shared' => 'Megosztva',
		'shares' => 'Megosztások',
		'unshare' => 'Megosztás visszavonása',

		'cancel' => 'Mégsem',
		'copyLink' => 'Link másolása',
		'delete' => 'Törlés',
		'error' => 'Hiba',
		'errorMsg' => 'Valami hiba történt. Próbáld újra később.',
		'oops' => 'Hoppá!',
		'other' => 'Egyéb',
		'readMore' => 'Tudj meg többet',
		'success' => 'Siker',

		'sensitive' => 'Érzékeny',
		'sensitiveContent' => 'Kényes tartalom',
		'sensitiveContentWarning' => 'Ez a poszt kényes tartalmat tartalmazhat',
	],

	'site' => [
		'terms' => 'Használati feltételek',
		'privacy' => 'Adatvédelmi irányelvek',
	],

	'navmenu' => [
		'search' => 'Keresés',
		'admin' => 'Admin irányítópult',

		// Timelines
		'homeFeed' => 'Kezdőlap',
		'localFeed' => 'Helyi idővonal',
		'globalFeed' => 'Föderációs idővonal',

		// Core features
		'discover' => 'Felfedezés',
		'directMessages' => 'Közvetlen üzenetek',
		'notifications' => 'Értesítések',
		'groups' => 'Csoportok',
		'stories' => 'Történetek',

		// Self links
		'profile' => 'Profil',
		'drive' => 'Meghajtó',
		'settings' => 'Beállítások',
		'compose' => 'Új létrehozása',

		// Nav footer
		'about' => 'Rólunk',
		'help' => 'Súgó',
		'language' => 'Nyelvek',
		'privacy' => 'Adatvédelem',
		'terms' => 'Feltételek',

		// Temporary links
		'backToPreviousDesign' => 'Vissza az előző kinézetre'
	],

	'directMessages' => [
		'inbox' => 'Bejövő',
		'sent' => 'Elküldött',
		'requests' => 'Kérelmek'
	],

	'notifications' => [
		'liked' => 'kedvelte %-t',
		'commented' => 'hozzászólt a %-hez',
		'reacted' => 'reagált a %-re',
		'shared' => 'megosztotta a %-t',
		'tagged' => 'megjelölt ebben',

		'updatedA' => 'frissítette a %-t',
		'sentA' => 'küldött egy %-t',

		'followed' => 'followed',
		'mentioned' => 'megemlített',
		'you' => 'te',

		'yourApplication' => 'Your application to join',
		'applicationApproved' => 'was approved!',
		'applicationRejected' => 'was rejected. You can re-apply to join in 6 months.',

		'dm' => 'dm',
		'groupPost' => 'group post',
		'modlog' => 'modlog',
		'post' => 'bejegyzés',
		'story' => 'történet',
	],

	'post' => [
		'shareToFollowers' => 'Megosztás a követőkkel',
		'shareToOther' => 'Megosztás másokkal',
		'noLikes' => 'Még nem kedveli senki',
		'uploading' => 'Feltöltés',
	],

	'profile' => [
		'posts' => 'Bejegyzések',
		'followers' => 'Követők',
		'following' => 'Követettek',
		'admin' => 'Adminisztrátor',
		'collections' => 'Gyűjtemények',
		'follow' => 'Követés',
		'unfollow' => 'Követés visszavonása',
		'editProfile' => 'Profil szerkesztése',
		'followRequested' => 'Követési kérelmek',
		'joined' => 'Csatlakozott',

		'emptyCollections' => 'We can\'t seem to find any collections',
		'emptyPosts' => 'We can\'t seem to find any posts',
	],

	'menu' => [
		'viewPost' => 'Bejegyzés megtekintése',
		'viewProfile' => 'Profil megtekintése',
		'moderationTools' => 'Moderációs eszközök',
		'report' => 'Bejelentés',
		'archive' => 'Archiválás',
		'unarchive' => 'Visszaállítás archívumból',
		'embed' => 'Beágyazás',

		'selectOneOption' => 'Kérjük, válassz egyet az alábbi lehetőségek közül',
		'unlistFromTimelines' => 'Unlist from Timelines',
		'addCW' => 'Tartalmi figyelmeztetés hozzádása',
		'removeCW' => 'Tartalmi figyelmeztetés törlése',
		'markAsSpammer' => 'Mark as Spammer',
		'markAsSpammerText' => 'Unlist + CW existing and future posts',
		'spam' => 'Spam',
		'sensitive' => 'Érzékeny tartalom',
		'abusive' => 'Abusive or Harmful',
		'underageAccount' => 'Underage Account',
		'copyrightInfringement' => 'Szerzői jogok megsértése',
		'impersonation' => 'Megszemélyesítés',
		'scamOrFraud' => 'Átverés vagy visszaélés',
		'confirmReport' => 'Confirm Report',
		'confirmReportText' => 'Biztos vagy benne, hogy jelenteni akarod ezt a bejegyzést?',
		'reportSent' => 'Jelentés elküldve!',
		'reportSentText' => 'We have successfully received your report.',
		'reportSentError' => 'There was an issue reporting this post.',

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
		'peopleYouMayKnow' => 'People you may know'
	],

	'hashtags' => [
		'emptyFeed' => 'We can\'t seem to find any posts for this hashtag'
	],

];
