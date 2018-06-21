<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TestTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_model', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('photo_id', false, true)->index('photo_id')->nullable();
            $table->integer('video_id', false, true)->index('video_id')->nullable();
            $table->integer('file_id', false, true)->index('file_id')->nullable();
            $table->timestamps();
        });

        Schema::create('test_model_photos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('photo_id', false, true)->index('photo_id');
            $table->integer('test_model_id', false, true)->index('test_model_id');
            $table->timestamps();
        });

        Schema::create('test_model_videos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('video_id', false, true)->index('video_id');
            $table->integer('test_model_id', false, true)->index('test_model_id');
            $table->timestamps();
        });

        Schema::create('test_model_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('file_id', false, true)->index('file_id');
            $table->integer('test_model_id', false, true)->index('test_model_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_model');
        Schema::dropIfExists('test_model_photos');
        Schema::dropIfExists('test_model_videos');
        Schema::dropIfExists('test_model_files');
    }
}
