<?php

use App\Util\Database\DatabaseDriver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_datasets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 16)->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('source_name', 255)->nullable();
            $table->unsignedBigInteger('source_size')->default(0);
            $table->string('path', 255)->nullable();
            $table->unsignedBigInteger('bytes_total')->default(0);
            $table->unsignedBigInteger('byte_offset')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('networks_v4')->default(0);
            $table->unsignedInteger('networks_v6')->default(0);
            $table->unsignedInteger('entities')->default(0);
            $table->boolean('allow_smaller')->default(false);
            $table->string('error', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('ip_country_networks', function (Blueprint $table) {
            $table->unsignedBigInteger('dataset_id');
            $this->addressKey($table, 'start');
            $table->unsignedTinyInteger('prefix');
            $table->char('country_code', 2);

            $table->primary(['dataset_id', 'start']);
        });

        Schema::create('ip_asn_networks', function (Blueprint $table) {
            $table->unsignedBigInteger('dataset_id');
            $this->addressKey($table, 'start');
            $table->unsignedTinyInteger('prefix');
            $table->unsignedBigInteger('asn');

            $table->primary(['dataset_id', 'start']);
        });

        Schema::create('ip_asns', function (Blueprint $table) {
            $table->unsignedBigInteger('asn')->primary();
            $table->string('name', 255)->nullable();
            $table->string('org', 255)->nullable();
            $table->string('domain', 255)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('ip_countries', function (Blueprint $table) {
            $table->char('code', 2)->primary();
            $table->char('continent_code', 2)->nullable();
            $table->string('name', 120);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_countries');
        Schema::dropIfExists('ip_asns');
        Schema::dropIfExists('ip_asn_networks');
        Schema::dropIfExists('ip_country_networks');
        Schema::dropIfExists('ip_datasets');
    }

    protected function addressKey(Blueprint $table, string $name): void
    {
        $column = $table->char($name, 32);

        if (DatabaseDriver::isMysqlMaria()) {
            $column->charset('ascii')->collation('ascii_bin');
        } elseif (DatabaseDriver::isPgsql()) {
            $column->collation('C');
        }
    }
};
