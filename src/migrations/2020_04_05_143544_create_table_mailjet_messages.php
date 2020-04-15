<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMailjetMessages extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::disableForeignKeyConstraints();
        Schema::create('mailjet_messages', function (Blueprint $table) {
            $table->bigIncrements(      'id');
            // custom id supplied by Mailjet/Mailer.
            $table->string(             'mailjet_request_id', 255)->nullable();
            // morphable relation
            $table->string(             'mailjet_messageble_type', 126)->nullable();
            $table->unsignedInteger(    'mailjet_messageble_id')->nullable();
            // used email
            $table->string(             'email');
            // message id's supplied by mailjet
            $table->bigInteger(         'mailjet_id')->nullable();
            $table->string(             'mailjet_uuid', 255)->nullable();
            // REST message location
            $table->string(             'mailjet_href', 120)->nullable();
            // template id supplied by mailjet
            $table->string(             'mailjet_template_id', 20)->nullable();
            // template name supplied by config.mailjet
            $table->string(             'template_name', 255)->nullable();
            // api version
            $table->string(             'version', 10);
            // if the api request was a success
            $table->boolean(            'success')->nullable();
            // status code
            $table->string(             'status', 10);
            // latest status supplied by mailjets webhook.
            $table->string(             'delivery_status', 18);
            // if sandboxed, only v3.1. Defaults to false.
            $table->boolean(            'sandbox')->default(false);
            $table->timestamps();
        });

        Schema::table('mailjet_messages', function(Blueprint $table) {
            $table->index('mailjet_request_id');
            $table->index('email');
            $table->index('mailjet_id');
            $table->index('mailjet_uuid');
            $table->index('mailjet_template_id');
            $table->index('template_name');
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
        Schema::dropIfExists('mailjet_messages');
        Schema::enableForeignKeyConstraints();
    }
}
