<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->foreignUuid('parent_id')->nullable()->default(null)->references('id')->on('units')->onUpdate('cascade')->onDelete('restrict'); //if 'UIW' than 'null' else 'valueable'
            $table->foreignId('level_id')->constrained()->onUpdate('cascade')->onDelete('restrict');
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
        Schema::dropIfExists('units');
    }
}
