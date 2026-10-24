<?php

return [
    'exception_message' => 'Mensaxe da exepción: :message',
    'exception_trace' => 'Traza da excepción: :trace',
    'exception_message_title' => 'Mensaxe da excepción',
    'exception_trace_title' => 'Traza da excepción',

    'backup_failed_subject' => 'Erro no respaldo de :application_name',
    'backup_failed_body' => 'Importante: Algo fallou ao respaldar :application_name',

    'backup_successful_subject' => 'Respaldo realizado correctamente :application_name',
    'backup_successful_subject_title' => 'Novo respaldo correcto!',
    'backup_successful_body' => 'Parabéns, un novo respaldo de :application_name foi realizado correctamente no disco con nome :disk_name.',

    'cleanup_failed_subject' => 'Limpando os respaldos de :application_name failed.',
    'cleanup_failed_body' => 'Algo fallou mentras se limpaban os respaldos de :application_name',

    'cleanup_successful_subject' => 'Limpeza correcta nos respaldos de :application_name',
    'cleanup_successful_subject_title' => 'Limpeza dos respaldos correcta!',
    'cleanup_successful_body' => 'Realizouse correctamente a limpeza dos respaldos de :application_name no disco con nome :disk_name.',

    'healthy_backup_found_subject' => 'Os respaldos de :application_name no disco :disk_name están en bo estado',
    'healthy_backup_found_subject_title' => 'Os respaldos de :application_name están ben!',
    'healthy_backup_found_body' => 'Os respaldos de :application_name están en bo estado. Bo traballo!',

    'unhealthy_backup_found_subject' => 'Importante: Os respaldos de :application_name non están en bo estado',
    'unhealthy_backup_found_subject_title' => 'Importante: Os respaldos de :application_name non están ben. :problem',
    'unhealthy_backup_found_body' => 'Os respaldos para :application_name no disco :disk_name non están ben.',
    'unhealthy_backup_found_not_reachable' => 'Non se puido alcanzar o disco de destino. :error',
    'unhealthy_backup_found_empty' => 'Non existen copias de respaldo para esta aplicación.',
    'unhealthy_backup_found_old' => 'O último respaldo realizouse en :date e considerase demasiado antigo.',
    'unhealthy_backup_found_unknown' => 'Lamentámolo, non se puido determinar unha causa concreta.',
    'unhealthy_backup_found_full' => 'Os respaldos están a utilizar demasiado espazo. A utilización actual de :disk_usage é maior que o límite establecido de :disk_limit.',
];
