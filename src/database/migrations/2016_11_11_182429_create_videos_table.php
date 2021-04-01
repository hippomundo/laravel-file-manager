<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('videos')) {
            Schema::create('videos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('original_path')->nullable();
                $table->string('path')->nullable();
                $table->string('folder_path')->nullable();
                $table->string('thumbnail_path')->nullable();
                $table->string('original_name')->nullable();
                $table->string('extension', 20)->nullable();
                $table->string('hash')->unique('video_hash')->nullable();
                $table->string('storage', 100)->nullable();
                $table->string('type', 100)->nullable();
                // bytes
                $table->integer('file_size')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('videos');
    }
}
