<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['idp', 'sp']);
            $table->string('entityid')->unique();
            $table->string('file')->unique();
            $table->longText("xml_file")->nullable();
            $table->string('name_en', 128)->nullable();
            $table->string('name_cs', 128)->nullable();
            $table->string('description_en')->nullable();
            $table->string('description_cs')->nullable();
            $table->boolean('edugain')->default(false);
            $table->boolean('rs')->default(false);
            $table->boolean('cocov1')->default(false);
            $table->boolean('sirtfi')->default(false);
            $table->boolean('approved')->default(false);
            $table->boolean('active')->default(true);
            $table->mediumText('metadata')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('entities');
    }
}
