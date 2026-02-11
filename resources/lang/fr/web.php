<?php

return [

	'common' => [
		'comment' => 'Commenter',
		'commented' => 'Commenté',
		'comments' => 'Commentaires',
		'like' => 'J\'aime',
		'liked' => 'Aimé',
		'likes' => 'J\'aimes',
		'share' => 'Partager',
		'shared' => 'Partagé',
		'shares' => 'Partages',
		'unshare' => 'Ne plus partager',
		'bookmark' => 'Marque-page',

		'cancel' => 'Annuler',
		'copyLink' => 'Copier le lien',
		'delete' => 'Supprimer',
		'error' => 'Erreur',
		'errorMsg' => 'Une erreur est survenue. Veuillez réessayer plus tard.',
		'oops' => 'Oups !',
		'other' => 'Autre',
		'readMore' => 'En savoir plus',
		'success' => 'Succès',
		'proceed' => 'Continuer',
		'next' => 'Suivant',
		'close' => 'Fermer',
		'clickHere' => 'cliquez ici',

		'sensitive' => 'Sensible',
		'sensitiveContent' => 'Contenu sensible',
		'sensitiveContentWarning' => 'Le contenu de cette publication peut être sensible',
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
		'appearance' => 'Apparence',
		'compose' => 'Publier',
		'logout' => 'Déconnexion',

		// Nav footer
		'about' => 'À propos',
		'help' => 'Aide',
		'language' => 'Langue',
		'privacy' => 'Confidentialité',
		'terms' => 'Conditions',
        'mobileApps' => 'Applis mobiles',

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

		'yourApplication' => 'Votre demande d\'adhésion',
		'applicationApproved' => 'a été approuvée !',
		'applicationRejected' => 'a été rejetée. Vous pouvez refaire une demande dans 6 mois.',

		'dm' => 'mp',
		'groupPost' => 'publication de groupe',
		'modlog' => 'journal de modération',
		'post' => 'publication',
		'story' => 'story',
		'noneFound' => 'Aucune notification trouvée',
	],

	'post' => [
		'shareToFollowers' => 'Partager avec ses abonné·e·s',
		'shareToOther' => 'Partager avec d\'autres',
		'noLikes' => 'Aucun J\'aime pour le moment',
		'uploading' => 'Téléversement',
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
		'followRequested' => 'Demande d\'abonnement faite',
		'joined' => 'A rejoint',

		'emptyCollections' => 'Aucune collection ne semble exister',
		'emptyPosts' => 'Il semble n’y avoir aucune publication',
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
		'markAsSpammerText' => 'Retirer des flux + ajouter un avertissement de contenu pour les publications existantes et futures',
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
		'peopleYouMayKnow' => 'Personnes que vous connaissez peut-être',

		'onboarding' => [
			'welcome' => 'Bienvenue',
			'thisIsYourHomeFeed' => 'Ceci est votre flux personnel, un flux chronologique des publications de comptes que vous suivez.',
			'letUsHelpYouFind' => 'Laissez-nous vous aider à trouver des personnes intéressantes à suivre',
			'refreshFeed' => 'Actualiser mon flux',
		],
	],

	'hashtags' => [
		'emptyFeed' => 'Aucune publication ne semble exister pour ce hashtag'
	],

	'report' => [
		'report' => 'Signaler',
		'selectReason' => 'Sélectionner un motif',
		'reported' => 'Signalé',
		'sendingReport' => 'Envoi du signalement',
		'thanksMsg' => 'Merci pour votre signalement, les gens comme vous aident à assurer la sécurité de notre communauté !',
		'contactAdminMsg' => 'Si vous souhaitez contacter un·e administrateur·trice à propos de cette publication ou de ce signalement',
	],

    'appearance' => [
        'theme' => 'Thème',
        'profileLayout' => 'Disposition du profil',
        'compactPreviews' => 'Prévisualisation compacte',
        'loadComments' => 'Charger les commentaires',
        'hideStats' => 'Masquer les nombres et statistiques',

        'auto' => 'Auto',
        'lightMode' => 'Mode clair',
        'darkMode' => 'Mode sombre',

        'grid' => 'Grille',
        'masonry' => 'Dallage',
        'feed' => 'Fil',
    ],

    'landing' => [
        'login'         =>    'Se connecter',
        'signup'        =>    'Créer un compte',
        'about'         =>    'À propos',
        'directory'     =>    'Annuaire',
        'explore'       =>    'Explorer',
        'decentralized_by_pixelfed' => 'Média social décentralisé de partage de photo, propulsé par <a href="https://pixelfed.org" target="_blank">Pixelfed</a>',
        'posts'         =>    'Publications',
        'active_users'  =>    'Utilisateurs actifs',
        'total_users'   =>    'Utilisateurs au total',
        'managed_by'    =>    'Géré par',
        'server_rules'  =>    'Règles du serveur',
        'supported_features' => 'Fonctionnalités prises en charge',
        'features' => [
            'photo_posts'   =>  'Publication de photos',
            'photo_albums'  =>  'Albums photos',
            'photo_filters' =>  'Filtres',
            'collections'   =>  'Collections',
            'comments'      =>  'Commentaires',
            'hashtags'      =>  'Hashtags',
            'likes'         =>  'J\'aime',
            'notifications' =>  'Notifications',
            'shares'        =>  'Partages',
            'share_up_to_n_photos' =>  'Vous pouvez partager jusqu\'à <span class="font-weight-bold">{num_photos}</span> photos* à la fois avec des légendes d\'une longueur maximale de <span class="font-weight-bold">{caption_length}</span> caractères.',
            'share_up_to_n_photos_videos'   =>  'Vous pouvez partager jusqu\'à <span class="font-weight-bold">{num_photos}</span> photos* or <span class="font-weight-bold">{num_video}</span> video* à la fois avec des légendes d\'une longueur maximale de <span class="font-weight-bold">{caption_length}</span> caractères.',
            'file_size'     => '* la taille maximale des fichiers est {max_size}',
            'federation'    => 'Fédération',
            'mobile_app'    => 'Application mobile',
            'stories'       => 'Stories',
            'videos'        => 'Vidéos',
        ],
        'discover_accounts' => 'Découvrir des comptes et personnes',
        'nothing_to_show'   => 'Rien à montrer pour le moment… Revenez plus tard.',
        'explore_trending'  => 'Parcourir les tendances',
        'powered_by_pixelfed' => 'Propulsé par Pixelfed',
    ],
];
