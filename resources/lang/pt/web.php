<?php

return [

	'common' => [
		'comment' => 'Comentário',
		'commented' => 'Comentado',
		'comments' => 'Comentários',
		'like' => 'Curtir',
		'liked' => 'Curtiu',
		'likes' => 'Curtidas',
		'share' => 'Compartilhar',
		'shared' => 'Compartilhado',
		'shares' => 'Compartilhamentos',
		'unshare' => 'Desfazer compartilhamento',

		'cancel' => 'Cancelar',
		'copyLink' => 'Copiar link',
		'delete' => 'Apagar',
		'error' => 'Erro',
		'errorMsg' => 'Algo deu errado. Por favor, tente novamente mais tarde.',
		'oops' => 'Opa!',
		'other' => 'Outro',
		'readMore' => 'Leia mais',
		'success' => 'Sucesso',

		'sensitive' => 'Sensível',
		'sensitiveContent' => 'Conteúdo sensível',
		'sensitiveContentWarning' => 'Esta publicação pode conter conteúdo inapropriado',
	],

	'site' => [
		'terms' => 'Termos de Uso',
		'privacy' => 'Política de Privacidade',
	],

	'navmenu' => [
		'search' => 'Pesquisar',
		'admin' => 'Painel do Administrador',

		// Timelines
		'homeFeed' => 'Página inicial',
		'localFeed' => 'Feed local',
		'globalFeed' => 'Feed global',

		// Core features
		'discover' => 'Explorar',
		'directMessages' => 'Mensagens privadas',
		'notifications' => 'Notificações',
		'groups' => 'Grupos',
		'stories' => 'Stories',

		// Self links
		'profile' => 'Perfil',
		'drive' => 'Drive',
		'settings' => 'Configurações',
		'compose' => 'Criar novo',

		// Nav footer
		'about' => 'Sobre',
		'help' => 'Ajuda',
		'language' => 'Idioma',
		'privacy' => 'Privacidade',
		'terms' => 'Termos',

		// Temporary links
		'backToPreviousDesign' => 'Voltar ao design anterior'
	],

	'directMessages' => [
		'inbox' => 'Caixa de entrada',
		'sent' => 'Enviadas',
		'requests' => 'Solicitações'
	],

	'notifications' => [
		'liked' => 'curtiu seu',
		'commented' => 'comentou em seu',
		'reacted' => 'reagiu ao seu',
		'shared' => 'compartilhou seu',
		'tagged' => 'marcou você em um',

		'updatedA' => 'atualizou um(a)',
		'sentA' => 'enviou um',

		'followed' => 'seguiu',
		'mentioned' => 'mencionado',
		'you' => 'você',

		'yourApplication' => 'Sua inscrição para participar',
		'applicationApproved' => 'foi aprovado!',
		'applicationRejected' => 'foi rejeitado. Você pode se inscrever novamente para participar em 6 meses.',

		'dm' => 'mensagem direta',
		'groupPost' => 'postagem do grupo',
		'modlog' => 'histórico de moderação',
		'post' => 'publicação',
		'story' => 'história',
	],

	'post' => [
		'shareToFollowers' => 'Compartilhar com os seguidores',
		'shareToOther' => 'Compartilhar com outros',
		'noLikes' => 'Ainda sem curtidas',
		'uploading' => 'Enviando',
	],

	'profile' => [
		'posts' => 'Publicações',
		'followers' => 'Seguidores',
		'following' => 'Seguindo',
		'admin' => 'Administrador',
		'collections' => 'Coleções',
		'follow' => 'Seguir',
		'unfollow' => 'Deixar de seguir',
		'editProfile' => 'Editar Perfil',
		'followRequested' => 'Solicitação de seguir enviada',
		'joined' => 'Entrou',

		'emptyCollections' => 'Não conseguimos encontrar nenhuma coleção',
		'emptyPosts' => 'Não encontramos nenhuma publicação',
	],

	'menu' => [
		'viewPost' => 'Ver publicação',
		'viewProfile' => 'Ver Perfil',
		'moderationTools' => 'Ferramentas de moderação',
		'report' => 'Denunciar',
		'archive' => 'Arquivo',
		'unarchive' => 'Desarquivar',
		'embed' => 'Incorporar',

		'selectOneOption' => 'Selecione uma das opções a seguir',
		'unlistFromTimelines' => 'Retirar das linhas do tempo',
		'addCW' => 'Adicionar aviso de conteúdo',
		'removeCW' => 'Remover aviso de conteúdo',
		'markAsSpammer' => 'Marcar como Spammer',
		'markAsSpammerText' => 'Retirar das linhas do tempo + adicionar aviso de conteúdo às publicações antigas e futuras',
		'spam' => 'Lixo Eletrônico',
		'sensitive' => 'Conteúdo sensível',
		'abusive' => 'Abusivo ou Prejudicial',
		'underageAccount' => 'Conta de menor de idade',
		'copyrightInfringement' => 'Violação de direitos autorais',
		'impersonation' => 'Roubo de identidade',
		'scamOrFraud' => 'Golpe ou Fraude',
		'confirmReport' => 'Confirmar denúncia',
		'confirmReportText' => 'Tem certeza de que deseja denunciar esta publicação?',
		'reportSent' => 'Denúncia enviada!',
		'reportSentText' => 'Nós recebemos sua denúncia com sucesso.',
		'reportSentError' => 'Houve um problema ao denunciar esse post.',

		'modAddCWConfirm' => 'Você tem certeza que deseja adicionar um aviso de conteúdo sensível nesse post?',
		'modCWSuccess' => 'Aviso de conteúdo sensível adicionado com sucesso',
		'modRemoveCWConfirm' => 'Você tem certeza que deseja remover o aviso de conteúdo sensível nesse post?',
		'modRemoveCWSuccess' => 'Aviso de conteúdo sensível removido com sucesso',
		'modUnlistConfirm' => 'Você tem certeza que deseja remover esse post da listagem?',
		'modUnlistSuccess' => 'Postagem removida da listagem com sucesso',
		'modMarkAsSpammerConfirm' => 'Você tem certeza que deseja denunciar esse usuário? Todas as postagens existentes e futuras serão removidas das linhas do tempo, e um aviso de conteúdo sensível será aplicado.',
		'modMarkAsSpammerSuccess' => 'Perfil denunciado com sucesso',

		'toFollowers' => 'para seguidores',

		'showCaption' => 'Mostrar legenda',
		'showLikes' => 'Mostrar curtidas',
		'compactMode' => 'Modo compacto',
		'embedConfirmText' => 'Ao usar de forma “embed”, você concorda com nossas',

		'deletePostConfirm' => 'Você tem certeza que deseja excluir esta publicação?',
		'archivePostConfirm' => 'Tem certeza que deseja arquivar esta publicação?',
		'unarchivePostConfirm' => 'Tem certeza que deseja desarquivar esta publicação?',
	],

	'story' => [
		'add' => 'Adicionar Story'
	],

	'timeline' => [
		'peopleYouMayKnow' => 'Pessoas que você talvez conheça'
	],

	'hashtags' => [
		'emptyFeed' => 'Não encontramos nenhuma publicação com esta hashtag'
	],

];
