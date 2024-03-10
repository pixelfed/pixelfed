<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Instance;
use App\Profile;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach(Instance::lazyById(50, 'id') as $instance) {
            $si = Profile::whereDomain($instance->domain)->whereNotNull('sharedInbox')->first();
            if($si && $si->sharedInbox) {
                $instance->shared_inbox = $si->sharedInbox;
                $instance->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
