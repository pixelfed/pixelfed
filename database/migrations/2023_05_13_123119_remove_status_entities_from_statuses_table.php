<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Status;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    	foreach(Status::whereNotNull('entities')->lazyById(200, 'id') as $status) {
    		if(!in_array($status->scope, ['public', 'unlisted', 'private'])) {
    			continue;
    		}
    		if(str_starts_with($status->entities, '{"urls')) {
	    		$status->entities = null;
	    		$status->save();
    		} else {
    			continue;
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
