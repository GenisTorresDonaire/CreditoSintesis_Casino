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
            $table->integer('precio');
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

        Schema::create('salas_blackjacks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->integer('id_partida')->references('id')->on('blackjacks')->onDelete('cascade');
            $table->integer('rondas')->references('rondas')->on('blackjacks')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('blackjacks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('turnos'); //Id del usuario al que le toca
            $table->integer('estados'); //Estado de la partida Ej: Repartiendo , apostando ...
            $table->integer('rondas');  //Numero de rondas que lleva
            $table->timestamps();
        });

        Schema::create('cartas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('figura');
            $table->string('numero');
            $table->integer('id_blackjack')->references('id')->on('blackjacks')->onDelete('cascade');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade')->nullable(true) ;
            $table->timestamps();
        });

        Schema::create('salas_ruletas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->integer('id_partida')->references('id')->on('ruletas')->onDelete('cascade');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('ruletas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estados'); // Apostando , girando , repartiendo 
            $table->integer('rondas');//Numero de tiradas que lleva la ruleta
            $table->string('ultimos_numeros')->nullable();
            $table->timestamps();
        });

        Schema::create('apuestas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_ruleta')->references('id')->on('ruletas')->onDelete('cascade');
            $table->integer('ronda_ruleta')->references('rondas')->on('ruletas')->onDelete('cascade');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->integer('creditos');
            $table->string('tipo'); // color, numero, fila, docena, par/impar, mitad
            $table->string('valor'); // numero o celda que ha apostado
            $table->timestamps();
        });

        Schema::create('apuestasbj', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_bj')->references('id')->on('blackjacks')->onDelete('cascade');
            $table->integer('ronda_bj')->references('rondas')->on('blackjacks')->onDelete('cascade');
            $table->integer('id_usuario')->references('id')->on('users')->onDelete('cascade');
            $table->integer('creditos');
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
