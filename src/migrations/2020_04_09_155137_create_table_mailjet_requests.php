<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMailjetRequests extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::dropIfExists('mailjet_requests');

        Schema::create('mailjet_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->json('recipients')->nullable();
            $table->string('subject')->nullable();
            $table->integer('template_id')->nullable();
            $table->string('template_name')->nullable();
            $table->boolean('template_language')->default(false);
            $table->json('variables')->nullable();
            $table->string('status')->nullable();
            $table->string('success')->nullable();
            $table->string('version');
            $table->boolean('sandbox')->nullable();
            $table->json('queue')->nullable();
            $table->timestamps();
        });

        Schema::table('mailjet_requests', function(Blueprint $table) {
            $table->index('template_id');
            $table->index('template_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('mailjet_requests');
    }
}
