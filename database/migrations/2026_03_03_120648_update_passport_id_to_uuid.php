<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   /**
    * Run the migrations.
    */
   public function up(): void
   {

       if (!Schema::hasTable('oauth_clients')) {
           return;
       }

       $driver = DB::getDriverName();

       // Detect current id type
       $column = DB::selectOne("
           SELECT data_type
           FROM information_schema.columns
           WHERE table_name = 'oauth_clients'
           AND column_name = 'id'
       ");

       if (!$column) {
           return;
       }

       // MySQL returns 'bigint'
       // PostgreSQL returns 'bigint'
       if ($column->data_type !== 'bigint') {
           // Already migrated (uuid / char / varchar)
           return;
       }

       DB::beginTransaction();

       try {

           // Add new UUID column to oauth_clients
           if ($driver === 'pgsql') {
               DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
               DB::statement('ALTER TABLE oauth_clients ADD COLUMN uuid uuid');
               DB::statement('UPDATE oauth_clients SET uuid = uuid_generate_v4()');
           } else {
               DB::statement('ALTER TABLE oauth_clients ADD COLUMN uuid CHAR(36)');
           }

           // Add UUID columns to related tables
           DB::statement('ALTER TABLE oauth_access_tokens ADD COLUMN client_uuid ' . ($driver === 'pgsql' ? 'uuid' : 'CHAR(36)'));
           DB::statement('ALTER TABLE oauth_auth_codes ADD COLUMN client_uuid ' . ($driver === 'pgsql' ? 'uuid' : 'CHAR(36)'));
           DB::statement('ALTER TABLE oauth_personal_access_clients ADD COLUMN client_uuid ' . ($driver === 'pgsql' ? 'uuid' : 'CHAR(36)'));

           // Generate UUIDs (MySQL only)
           if ($driver !== 'pgsql') {
               $clients = DB::table('oauth_clients')->select('id')->get();

               foreach ($clients as $client) {
                   $uuid = (string) Str::uuid();

                   DB::table('oauth_clients')
                       ->where('id', $client->id)
                       ->update(['uuid' => $uuid]);

                   DB::table('oauth_access_tokens')
                       ->where('client_id', $client->id)
                       ->update(['client_uuid' => $uuid]);

                   DB::table('oauth_auth_codes')
                       ->where('client_id', $client->id)
                       ->update(['client_uuid' => $uuid]);

                   DB::table('oauth_personal_access_clients')
                       ->where('client_id', $client->id)
                       ->update(['client_uuid' => $uuid]);
               }
           } else {
               // PostgreSQL bulk copy
               DB::statement('
                   UPDATE oauth_access_tokens t
                   SET client_uuid = c.uuid
                   FROM oauth_clients c
                   WHERE t.client_id = c.id
               ');

               DB::statement('
                   UPDATE oauth_auth_codes t
                   SET client_uuid = c.uuid
                   FROM oauth_clients c
                   WHERE t.client_id = c.id
               ');

               DB::statement('
                   UPDATE oauth_personal_access_clients t
                   SET client_uuid = c.uuid
                   FROM oauth_clients c
                   WHERE t.client_id = c.id
               ');
           }

           // Drop PK & old columns
           if ($driver === 'pgsql') {
               DB::statement('ALTER TABLE oauth_clients DROP CONSTRAINT oauth_clients_pkey CASCADE');
           } else {
               DB::statement('ALTER TABLE oauth_clients DROP PRIMARY KEY');
           }

           DB::statement('ALTER TABLE oauth_clients DROP COLUMN id');
           DB::statement('ALTER TABLE oauth_access_tokens DROP COLUMN client_id');
           DB::statement('ALTER TABLE oauth_auth_codes DROP COLUMN client_id');
           DB::statement('ALTER TABLE oauth_personal_access_clients DROP COLUMN client_id');

           // Rename UUID columns
           DB::statement('ALTER TABLE oauth_clients RENAME COLUMN uuid TO id');
           DB::statement('ALTER TABLE oauth_access_tokens RENAME COLUMN client_uuid TO client_id');
           DB::statement('ALTER TABLE oauth_auth_codes RENAME COLUMN client_uuid TO client_id');
           DB::statement('ALTER TABLE oauth_personal_access_clients RENAME COLUMN client_uuid TO client_id');

           DB::statement('ALTER TABLE oauth_clients ADD PRIMARY KEY (id)');

           DB::commit();

       } catch (\Throwable $e) {
           DB::rollBack();
           throw $e;
       }

   }

   /**
    * Reverse the migrations.
    */
   public function down(): void
   {
       Schema::table('uuid', function (Blueprint $table) {
           // Reverse not possible 
       });
   }
};
