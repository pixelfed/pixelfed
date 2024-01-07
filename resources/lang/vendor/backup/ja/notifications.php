<?php

return [
    'exception_message' => '例外メッセージ: :message',
    'exception_trace' => '例外トレース: :trace',
    'exception_message_title' => '例外メッセージ',
    'exception_trace_title' => '例外トレース',

    'backup_failed_subject' => 'バックアップに失敗しました :application_name',
    'backup_failed_body' => '重要:バックアップ中にエラーが発生しました :application_name',

    'backup_successful_subject' => ':application_name のバックアップに成功しました',
    'backup_successful_subject_title' => 'バックアップに成功しました',
    'backup_successful_body' => ':application_name のバックアップは :disk_name に正常に作成されました',

    'cleanup_failed_subject' => ':application_name のバックアップのクリーンアップに失敗しました',
    'cleanup_failed_body' => ':application_name のバックアップのクリーンアップ中にエラーが発生しました',

    'cleanup_successful_subject' => ':application_name のバックアップのクリーンアップに成功しました',
    'cleanup_successful_subject_title' => 'バックアップのクリーンアップに成功しました',
    'cleanup_successful_body' => ':disk_name 上の :application_name のバックアップのクリーンアップに成功しました',

    'healthy_backup_found_subject' => ':disk_name 上の :application_name のバックアップは正常です',
    'healthy_backup_found_subject_title' => ':application_name のバックアップは正常です',
    'healthy_backup_found_body' => ':application_name のバックアップは正常に見られます',

    'unhealthy_backup_found_subject' => '重要: :application_name のバックアップに問題があります',
    'unhealthy_backup_found_subject_title' => '重要: :application_name のバックアップに問題があります :problem',
    'unhealthy_backup_found_body' => ':disk_name 上の :application_name のバックアップに問題があります',
    'unhealthy_backup_found_not_reachable' => 'バックアップ先に到達できません。 :error',
    'unhealthy_backup_found_empty' => 'このアプリケーションのバックアップはありません。',
    'unhealthy_backup_found_old' => ':date に作成されたバックアップは古すぎます。',
    'unhealthy_backup_found_unknown' => '正確な原因が特定できませんでした。',
    'unhealthy_backup_found_full' => 'バックアップが使用できる容量(:disk_limit)を超えています。(現在の使用量 :disk_usage)',
];
