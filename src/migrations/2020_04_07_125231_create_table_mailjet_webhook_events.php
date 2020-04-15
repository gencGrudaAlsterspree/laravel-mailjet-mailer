<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMailjetWebhookEvents extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::disableForeignKeyConstraints();
        Schema::create('mailjet_webhook_events', function (Blueprint $table) {
            $table->bigIncrements(  'id');
            $table->string(         'mailjet_request_id', 255)->nullable();
            $table->bigInteger(     'mailjet_id');
            $table->string(         'mailjet_uuid', 255);
            $table->string(         'event', 18);
            $table->timestamp(      'time');
            $table->json(           'data');
            $table->timestamps();
        });

        Schema::table('mailjet_webhook_events', function (Blueprint $table) {
            $table->index('custom_id');
            $table->index('mailjet_id');
            $table->index('mailjet_uuid');
            $table->index('event');
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('mailjet_webhook_events');
        Schema::enableForeignKeyConstraints();
    }
}
