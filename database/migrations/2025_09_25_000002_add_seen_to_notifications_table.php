<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeenToNotificationsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('notifications', 'seen')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->boolean('seen')->default(false)->after('recorded_at');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('notifications', 'seen')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('seen');
            });
        }
    }
}
