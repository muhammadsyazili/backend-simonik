<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->primary('id');
            $table->string('nip')->nullable()->default(null);
            $table->string('name');
            $table->string('username')->unique();
            $table->boolean('actived')->default(0);
            $table->string('password');
            $table->foreignUuid('unit_id')->nullable()->default(null)->constrained()->onUpdate('cascade')->onDelete('restrict'); //if 'super-admin' than 'null' else 'valueable'
            $table->foreignId('role_id')->constrained()->onUpdate('cascade')->onDelete('restrict');
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
        Schema::dropIfExists('users');
    }
}
