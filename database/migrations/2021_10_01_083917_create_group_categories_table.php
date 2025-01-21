<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\GroupCategory;

class CreateGroupCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('group_categories');

        Schema::create('group_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->index();
            $table->string('slug')->unique()->index();
            $table->boolean('active')->default(true)->index();
            $table->tinyInteger('order')->unsigned()->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $default = [
        	'General',
			'Photography',
			'Fediverse',
			'CompSci & Programming',
			'Causes & Movements',
			'Humor',
			'Science & Tech',
			'Travel',
			'Buy & Sell',
			'Business',
			'Style',
			'Animals',
			'Sports & Fitness',
			'Education',
			'Arts',
			'Entertainment',
			'Faith & Spirituality',
			'Relationships & Identity',
			'Parenting',
			'Hobbies & Interests',
			'Food & Drink',
			'Vehicles & Commutes',
			'Civics & Community',
		];

		for ($i=1; $i <= 23; $i++) {
			$cat = new GroupCategory;
			$cat->name = $default[$i - 1];
			$cat->slug = str_slug($cat->name);
			$cat->active = true;
			$cat->order = $i;
			$cat->save();
		}

		Schema::table('groups', function (Blueprint $table) {
			$table->unsignedInteger('category_id')->default(1)->index()->after('id');
			$table->unsignedInteger('member_count')->nullable();
			$table->boolean('recommended')->default(false)->index();
			$table->boolean('discoverable')->default(false)->index();
			$table->boolean('activitypub')->default(false);
			$table->boolean('is_nsfw')->default(false);
			$table->boolean('dms')->default(false);
			$table->boolean('autospam')->default(false);
			$table->boolean('verified')->default(false);
			$table->timestamp('last_active_at')->nullable();
			$table->softDeletes();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_categories');

		Schema::table('groups', function (Blueprint $table) {
			$table->dropColumn('category_id');
			$table->dropColumn('member_count');
			$table->dropColumn('recommended');
			$table->dropColumn('activitypub');
			$table->dropColumn('is_nsfw');
			$table->dropColumn('discoverable');
			$table->dropColumn('dms');
			$table->dropColumn('autospam');
			$table->dropColumn('verified');
			$table->dropColumn('last_active_at');
			$table->dropColumn('deleted_at');
		});
    }
}
