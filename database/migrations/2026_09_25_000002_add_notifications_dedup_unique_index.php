<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->collapseDuplicates();

        Schema::table('notifications', function (Blueprint $table) {
            $table->unique(
                ['profile_id', 'actor_id', 'action', 'item_id', 'item_type'],
                'notifications_dedup_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('notifications_dedup_unique');
        });
    }

    protected function collapseDuplicates(): void
    {
        $groups = DB::table('notifications')
            ->select('profile_id', 'actor_id', 'action', 'item_id', 'item_type', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('actor_id')
            ->whereNotNull('action')
            ->whereNotNull('item_id')
            ->whereNotNull('item_type')
            ->groupBy('profile_id', 'actor_id', 'action', 'item_id', 'item_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            DB::table('notifications')
                ->where('profile_id', $group->profile_id)
                ->where('actor_id', $group->actor_id)
                ->where('action', $group->action)
                ->where('item_id', $group->item_id)
                ->where('item_type', $group->item_type)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }
    }
};
