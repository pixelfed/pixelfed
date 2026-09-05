<?php

return [
    'exception_message' => '例外訊息： :message',
    'exception_trace' => '例外追蹤： :trace',
    'exception_message_title' => '例外訊息',
    'exception_trace_title' => '例外追蹤',

    'backup_failed_subject' => '備份 :application_name 失敗',
    'backup_failed_body' => '重要：備份 :application_name 時遇到錯誤',

    'backup_successful_subject' => '成功備份 :application_name',
    'backup_successful_subject_title' => '成功的新備份！',
    'backup_successful_body' => '好消息， :application_name 的新備份成功地在名為 :disk_name 的磁碟上建立完成了。',

    'cleanup_failed_subject' => '清理 :application_name 的備份失敗。',
    'cleanup_failed_body' => '清理 :application_name 的備份時遇到錯誤。',

    'cleanup_successful_subject' => '清理 :application_name 的備份成功',
    'cleanup_successful_subject_title' => '清除備份成功！',
    'cleanup_successful_body' => '清除在名為 :disk_name 的磁碟上的 :application_name 備份成功了。',

    'healthy_backup_found_subject' => ':application_name 在磁碟 :disk_name 上的備份是健康的',
    'healthy_backup_found_subject_title' => ':application_name 的備份是健康的',
    'healthy_backup_found_body' => ':application_name 的備份看起來是健康的。幹得好！',

    'unhealthy_backup_found_subject' => '重要：:application_name 的備份有問題',
    'unhealthy_backup_found_subject_title' => '重要：:application_name 的備份有問題。 :problem',
    'unhealthy_backup_found_body' => ':application_name 在磁碟 :disk_name 上的備份是有問題的的。',
    'unhealthy_backup_found_not_reachable' => '備份目的地不可用。 :error',
    'unhealthy_backup_found_empty' => '完全沒有此應用程式的備份。',
    'unhealthy_backup_found_old' => '在 :date 製作的最新備份太舊了。',
    'unhealthy_backup_found_unknown' => '抱歉，無法確定確切的原因。',
    'unhealthy_backup_found_full' => '備份使用太多儲存空間了。目前的使用量為 :disk_usage ，這已經高於允許使用的限制 :disk_limit 了。',
];
