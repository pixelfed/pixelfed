<?php

return [
    'exception_message' => 'Salbuespen mezua: :message',
    'exception_trace' => 'Salbuespen aztarna: :trace',
    'exception_message_title' => 'Salbuespen mezua',
    'exception_trace_title' => 'Salbuespen aztarna',

    'backup_failed_subject' => 'Akatsa :application_name babeskopia egiterakoan',
    'backup_failed_body' => 'Garrantzitsua: Akatsa gertatu da :application_name babeskopia egiterakoan',

    'backup_successful_subject' => ':application_name babeskopia arrakastatsua',
    'backup_successful_subject_title' => 'Babeskopia arrakastatsu berria!',
    'backup_successful_body' => 'Berri onak, :application_name -ren babeskopia berria arrakastaz sortu da :disk_name izeneko diskoan.',

    'cleanup_failed_subject' => ':application_name -ren babeskopiak garbitzean akatsa.',
    'cleanup_failed_body' => 'Akatsa gertatu da :application_name -ren babeskopiak garbitzerakoan',

    'cleanup_successful_subject' => ':application_name -ren babeskopiak arrakastaz garbituta',
    'cleanup_successful_subject_title' => 'Babeskopien garbitze arrakastatsua!',
    'cleanup_successful_body' => ':application_name -ren babeskopia garbitzea arrakastaz gauzatu da :disk_name izeneko diskoan.',

    'healthy_backup_found_subject' => ':application_name -rentzat diren babeskopiak osasuntsu daude :disk_name diskoan',
    'healthy_backup_found_subject_title' => ':application_name -rentzat diren babeskopiak osasuntsu daude',
    'healthy_backup_found_body' => ':application_name -rentzat diren babeskopiak osasuntsutzat jotzen dira. Lan bikaina!',

    'unhealthy_backup_found_subject' => 'Garrantzitsua: :application_name -rentzat diren babeskopiak ez daude osasuntsu',
    'unhealthy_backup_found_subject_title' => 'Garrantzitsua: :application_name -rentzat diren babeskopiak ez daude osasuntsu. :problem',
    'unhealthy_backup_found_body' => ':application_name -rentzat diren babeskopiak ez daude osasuntsu :disk_name diskoan.',
    'unhealthy_backup_found_not_reachable' => 'Babeskopien helburua ezin izan da atzitu. :error',
    'unhealthy_backup_found_empty' => 'Ez dago aplikazio honen babeskopiarik.',
    'unhealthy_backup_found_old' => 'Azkena .date -n egindako babeskopia zaharregitzat jotzen da.',
    'unhealthy_backup_found_unknown' => 'Barkatu, ezin da arrazoi zehatza zehaztu.',
    'unhealthy_backup_found_full' => 'Babeskopiak leku gehiegi erabiltzen ari dira. Egungo erabilera :disk_usage -koa da, non, onartutako :disk_limit muga baino handiagoa den.',
];
