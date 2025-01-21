<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('curated_registers', function (Blueprint $table) {
            $table->boolean('user_has_responded')->default(false)->index()->after('is_awaiting_more_info');
        });

        CuratedRegisterActivity::whereFromUser(true)->get()->each(function($cra) {
            $cr = CuratedRegister::find($cra->register_id);
            $cr->user_has_responded = true;
            $cr->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curated_registers', function (Blueprint $table) {
            $table->dropColumn('user_has_responded');
        });
    }
};
