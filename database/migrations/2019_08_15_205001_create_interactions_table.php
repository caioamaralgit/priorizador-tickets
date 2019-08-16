<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInteractionsTable extends Migration
{
    public function up()
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('tickets_id');
            $table->string('subject');
            $table->text('message');
            $table->string('sender', 10);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tickets_id')->references('id')->on('tickets')->name('fk_interactions_ticket');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('interactions');
    }
}
