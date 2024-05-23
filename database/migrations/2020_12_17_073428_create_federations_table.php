<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFederationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('federations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32)->unique();
            $table->string('description');
            $table->string('tagfile', 36)->nullable()->default(null);
            $table->string('cfgfile', 36)->nullable()->default(null);
            $table->string('xml_id', 128)->unique();
            $table->string('xml_name', 128)->unique();
            $table->string('filters')->unique();
            $table->boolean('approved')->default(0);
            $table->boolean('active')->default(0);
            $table->string('explanation');
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
        Schema::dropIfExists('federations');
    }
}
