<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

return [

    /*
    |----------------------------------------------------------------------
    | Auto backup mode
    |----------------------------------------------------------------------
    |
    | This value is used when you save your file content. If value is true,
    | the original file will be backed up before save.
    */

    'autoBackup' => true,

    /*
    |----------------------------------------------------------------------
    | Backup location
    |----------------------------------------------------------------------
    |
    | This value is used when you backup your file. This value is the sub
    | path from root folder of project application.
    */

    'backupPath' => base_path('storage/dotenv-editor/backups/'),

];
