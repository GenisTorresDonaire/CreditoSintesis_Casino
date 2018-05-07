<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {  
        
        Schema::create('tiendas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('objeto');
            $table->string('precio');
            $table->timestamps();
        });

        Schema::create('pertenencias', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->integer('id_objeto')->references('id')->on('tienda')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('contabilidades', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->string('accion');
            $table->integer('creditos');
            $table->timestamps();
        });

        Schema::create('blackjacks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('turnos');
            $table->string('estados');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('cartas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('figura');
            $table->string('numero');
            $table->integer('id_blackjack')->references('id')->on('blackjacks')->onDelete('cascade');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('ruletas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('turnos');
            $table->string('estados');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('apuestas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_ruleta')->references('id')->on('ruletas')->onDelete('cascade');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->integer('creditos');
            $table->string('tipo');
            $table->string('valor');
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
        //
    }
}
