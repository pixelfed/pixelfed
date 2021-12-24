<?php

return [

	'common' => [
		'comment' => 'Commenter',
		'commented' => 'Commenté',
		'comments' => 'Commentaires',
		'like' => 'J\'aime',
		'liked' => 'Aimé',
		'likes' => 'J\'aime',
		'share' => 'Partager',
		'shared' => 'Partagé',
		'shares' => 'Partages',
		'unshare' => 'Ne plus partager',

		'cancel' => 'Annuler',
		'copyLink' => 'Copier le lien',
		'delete' => 'Supprimer',
		'error' => 'Erreur',
		'errorMsg' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
		'oops' => 'Zut !',
		'other' => 'Autre',
		'readMore' => 'En savoir plus',
		'success' => 'Opération réussie',

		'sensitive' => 'Sensible',
		'sensitiveContent' => 'Contenu sensible',
		'sensitiveContentWarning' => 'Le contenu de ce message peut être sensible',
	],

	'site' => [
		'terms' => 'Conditions d\'utilisation',
		'privacy' => 'Politique de confidentialité',
	],

	'navmenu' => [
		'search' => 'Chercher',
		'admin' => 'Tableau de bord d\'administration',

		// Timelines
		'homeFeed' => 'Fil principal',
		'localFeed' => 'Fil local',
		'globalFeed' => 'Fil global',

		// Core features
		'discover' => 'Découvrir',
		'directMessages' => 'Messages Privés',
		'notifications' => 'Notifications',
		'groups' => 'Groupes',
		'stories' => 'Stories',

		// Self links
		'profile' => 'Profil',
		'drive' => 'Médiathèque',
		'settings' => 'Paramètres',
		'compose' => 'Publier',

		// Nav footer
		'about' => 'À propos',
		'help' => 'Aide',
		'language' => 'Langue',
		'privacy' => 'Confidentialité',
		'terms' => 'Conditions',

		// Temporary links
		'backToPreviousDesign' => 'Revenir au design précédent'
	],

	'directMessages' => [
		'inbox' => 'Boîte de réception',
		'sent' => 'Boîte d\'envois',
		'requests' => 'Demandes'
	],

	'notifications' => [
		'liked' => 'a aimé votre',
		'commented' => 'a commenté votre',
		'reacted' => 'a réagi à votre',
		'shared' => 'a partagé votre',
		'tagged' => 'vous a tagué·e dans un',

		'updatedA' => 'mis à jour un·e',
		'sentA' => 'a envoyé un·e',

		'followed' => 's\'est abonné·e à',
		'mentioned' => 'a mentionné',
		'you' => 'vous',

		'yourApplication' => 'Votre candidature à rejoindre',
		'applicationApproved' => 'a été approuvée !',
		'applicationRejected' => 'a été rejetée. Vous pouvez refaire une demande dans 6 mois.',

		'dm' => 'mp',
		'groupPost' => 'publication de groupe',
		'modlog' => 'journal de modération',
		'post' => 'publication',
		'story' => 'story',
	],

	'post' => [
		'shareToFollowers' => 'Partager avec ses abonné·e·s',
		'shareToOther' => 'Partager avec d\'autres',
		'noLikes' => 'Aucun J\'aime pour le moment',
		'uploading' => 'Envoi en cours',
	],

	'profile' => [
		'posts' => 'Publications',
		'followers' => 'Abonné·e·s',
		'following' => 'Abonnements',
		'admin' => 'Administrateur·rice',
		'collections' => 'Collections',
		'follow' => 'S\'abonner',
		'unfollow' => 'Se désabonner',
		'editProfile' => 'Modifier votre profil',
		'followRequested' => 'Demande d\'abonnement',
		'joined' => 'A rejoint',
	],

	'menu' => [
		'viewPost' => 'Voir la publication',
		'viewProfile' => 'Voir le profil',
		'moderationTools' => 'Outils de modération',
		'report' => 'Signaler',
		'archive' => 'Archiver',
		'unarchive' => 'Désarchiver',
		'embed' => 'Intégrer',

		'selectOneOption' => 'Sélectionnez l\'une des options suivantes',
		'unlistFromTimelines' => 'Retirer des flux',
		'addCW' => 'Ajouter un avertissement de contenu',
		'removeCW' => 'Enlever l’avertissement de contenu',
		'markAsSpammer' => 'Marquer comme spammeur·euse',
		'markAsSpammerText' => 'Retirer + avertissements pour les contenus existants et futurs',
		'spam' => 'Indésirable',
		'sensitive' => 'Contenu sensible',
		'abusive' => 'Abusif ou préjudiciable',
		'underageAccount' => 'Compte d\'un·e mineur·e',
		'copyrightInfringement' => 'Violation des droits d’auteur',
		'impersonation' => 'Usurpation d\'identité',
		'scamOrFraud' => 'Arnaque ou fraude',
		'confirmReport' => 'Confirmer le signalement',
		'confirmReportText' => 'Êtes-vous sûr·e de vouloir signaler cette publication ?',
		'reportSent' => 'Signalement envoyé !',
		'reportSentText' => 'Nous avons bien reçu votre signalement.',
		'reportSentError' => 'Une erreur s\'est produite lors du signalement de cette publication.',

		'modAddCWConfirm' => 'Êtes-vous sûr·e de vouloir ajouter un avertissement de contenu à cette publication ?',
		'modCWSuccess' => 'Avertissement de contenu ajouté avec succès',
		'modRemoveCWConfirm' => 'Êtes-vous sûr·e de vouloir supprimer l\'avertissement de contenu sur cette publication ?',
		'modRemoveCWSuccess' => 'Avertissement de contenu supprimé avec succès',
		'modUnlistConfirm' => 'Êtes-vous sûr·e de vouloir retirer cette publication des flux ?',
		'modUnlistSuccess' => 'Publication retirée des fils avec succès',
		'modMarkAsSpammerConfirm' => 'Êtes-vous sûr·e de vouloir marquer cet utilisateur·rice comme spammeur·euse ? Toutes les publications existantes et futures seront retirées des flux et un avertissement de contenu sera appliqué.',
		'modMarkAsSpammerSuccess' => 'Compte marqué avec succès comme spammeur',

		'toFollowers' => 'aux abonné·e·s',

		'showCaption' => 'Afficher la légende',
		'showLikes' => 'Afficher les J\'aime',
		'compactMode' => 'Mode compact',
		'embedConfirmText' => 'En utilisant ce module, vous acceptez nos',

		'deletePostConfirm' => 'Êtes-vous sûr·e de vouloir supprimer cette publication ?',
		'archivePostConfirm' => 'Êtes-vous sûr·e de vouloir archiver cette publication ?',
		'unarchivePostConfirm' => 'Êtes-vous sûr·e de vouloir désarchiver cette publication ?',
	],

	'story' => [
		'add' => 'Ajouter une story'
	],

	'timeline' => [
		'peopleYouMayKnow' => 'Connaissances possibles'
	]

];
