<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndicatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('indicators', function (Blueprint $table) {
            //core
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('indicator', 255);
            $table->string('formula', 255)->nullable()->default(null);
            $table->string('measure', 100)->nullable()->default(null);
            $table->json('weight')->nullable()->default(null);
            $table->enum('polarity', ['1', '0', '-1'])->nullable()->default(null);
            $table->year('year')->nullable()->default(null); //if 'super-master' than 'null' else 'valueable'
            $table->boolean('reducing_factor')->nullable()->default(0);
            $table->json('validity')->nullable()->default(null);
            $table->boolean('reviewed')->default(0);
            $table->boolean('referenced')->default(0);
            $table->boolean('dummy')->default(0);
            $table->string('type', 255);
            $table->json('fdx')->nullable()->default(null);
            //support
            $table->enum('label', ['super-master', 'master', 'child']); //if 'super-master' than 'null' else 'valueable'
            $table->foreignUuid('unit_id')->nullable()->default(null)->constrained()->onUpdate('cascade')->onDelete('restrict'); //if 'super-master & master' than 'null' else 'valueable'
            $table->foreignId('level_id')->constrained()->onUpdate('cascade')->onDelete('restrict'); //[super-master, uiw, up3, ulp, up2k, up2d]
            $table->integer('order');
            $table->uuid('code')->nullable()->default(null); //static ID from super-master
            $table->foreignUuid('parent_vertical_id')->nullable()->default(null)->references('id')->on('indicators')->onUpdate('cascade')->onDelete('restrict'); //if 'super-master' than 'null' else 'valueable'
            $table->foreignUuid('parent_horizontal_id')->nullable()->default(null)->references('id')->on('indicators')->onUpdate('cascade')->onDelete('restrict'); //if 'root' than 'null' else 'valueable'
            $table->foreignUuid('created_by')->nullable()->default(null)->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
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
        Schema::dropIfExists('indicators');
    }
}
