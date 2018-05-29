<?php

/*
|----------------------------------------------
|En las funciones listaG y puntos fuerzo el id usuario para la demo!
|
|----------------------------------------------
*/

use Illuminate\Http\Request;
use App\Apuesta;
use App\ApuestasBJ;
use App\SalaBJ;
use App\BlackJack;
use App\Carta;
use App\Contabilidad;
use App\Pertenencia;
use App\Ruleta;
use App\Tienda;
use App\User;
use App\Salas_ruleta;


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
|
*/
global $cartas;
$cartas=array();

global $arrayPuntosJugadores;
$arrayPuntosJugadores= array();

global $arrayNumerosRuleta;
$arrayNumerosRuleta = array(0,32,15,19,4,21,2,25,17,34,6,27,13,36,11,30,8,32,10,5,24,16,33,1,20,14,31,9,22,18,29,7,28,12,35,3,26);

global $arrayNumerosRuletaColores;
$arrayNumerosRuletaColores = array( 0 => [0, 'verde'],32 => [32, 'rojo'],15 => [15, 'negro'],19 => [19, 'rojo'],4 => [4, 'negro'],21 => [21, 'rojo'],2 => [2, 'negro'],25 => [25, 'rojo'],17 => [17, 'negro'],34 => [34, 'rojo'],6 => [6, 'negro'],27 => [27, 'rojo'],13 => [13, 'negro'],36 => [36, 'rojo'],11 => [11, 'negro'],30 => [30, 'rojo'],8 => [8, 'negro'],32 => [32, 'rojo'],10 => [10, 'negro'],5 => [5, 'rojo'],24 => [24, 'negro'],16 => [16, 'rojo'],33 => [33, 'negro'],1 => [1, 'rojo'],20 => [20, 'negro'],14 => [14, 'rojo'],31 => [31, 'negro'],9 => [9, 'rojo'],22 => [22, 'rojo'],18 => [18, 'rojo'],29 => [29, 'negro'],7 => [7, 'rojo'],28 => [28, 'negro'],12 => [12, 'rojo'],35 => [35, 'negro'],3 => [3, 'rojo'],26 => [26, 'negro']);



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/
Route::get('/login/{usuario}/{password}', function ($usuario, $password) {
    // Con el Auth::attempt devolvera el usuario, que contenga ese name y esa contraseña
    if( Auth::attempt(['name' => $usuario, 'password' => $password])){		
		// Ahora obtendremos información, de si el cliente ha sido 
    	$token_usuario = User::where('name', $usuario )->select('token')->get();	
		
		// Si no tiene ningún token, se le asignara uno.
		if( $token_usuario[0]['token'] == 0){

			// Generación del token
			$rand_part = str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789".uniqid());
			
			// Actualización del token, para insertar el token generado a dicho usuario.
			User::where('name', $usuario )->update(['token' => $rand_part]);
			
			// Inserción de los CORS y envio del JSON al cliente.
			header("Access-Control-Allow-Origin: *");
    		return json_encode( array('status' => 'ok', 'token' => $rand_part) );

    	}else{
    		// Inserción de los CORS y envio del JSON al cliente.
    		header("Access-Control-Allow-Origin: *");
    		return json_encode( array('status' => 'ok', 'token' => $token_usuario[0]['token']) );
    	}
	}
	else{
		// Inserción de los CORS y envio del JSON al cliente.
    	header("Access-Control-Allow-Origin: *");
		return json_encode( array('status' => 'error', 'mensaje' => 'Usuario o contraseña, incorrectos.') );
	}
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});




/*
|--------------------------------------------------------------------------
| Ruleta
|--------------------------------------------------------------------------
*/
// Obtener creditos del usuario, con el token
Route::get('/creditos/{token}', function($token){
	$creditos = User::where('token', $token )->get();

	header("Access-Control-Allow-Origin: *");
	return json_encode(array(['status' => 'ok', 'creditos' => $creditos[0]['creditos']]));
});


// Funcion para entrar a un room
Route::get('/entrar_room/{id_ruleta}/{token}', function ($id_ruleta, $token) {
	// Posibles colores en la sala
	$arrayColores = array(0 => 'verde', 1 => 'amarillo', 2 => 'azul');
	$id_usuario = obtenerIdUsuario($token);

	// Numero de jugadores de la sala
	$nuemeroJugadores = jugadores_sala_ruleta($id_ruleta);
	$infoAntes = Salas_ruleta::where('id_partida', $id_ruleta)->where('id_usuario', $id_usuario)->get();
	
	// Si en la sala hay menos de 3 usuarios, y el que se va a insertar no esta, entrara
	if ( $nuemeroJugadores < 3 && sizeof($infoAntes) == 0 ){
		// Al entrar a una sala se elimina de las demas
		Salas_ruleta::where('id_usuario', $id_usuario)->delete();
		$color = "";

		// Control para saber que colores asignar
		if ( $nuemeroJugadores == 0 ){
			$color = $arrayColores[0];
		}else if ( $nuemeroJugadores == 1 ){
			$color = $arrayColores[1];
		}else if ( $nuemeroJugadores == 2 ){
			$color = $arrayColores[2];
		}

		// Creacion de la sala, con el usuario y su color
		$nueva = new Salas_ruleta();
		$nueva->id_partida = $id_ruleta;
		$nueva->id_usuario = $id_usuario;
		$nueva->color = $color;
		$nueva->save();	

		header("Access-Control-Allow-Origin: *");
		return json_encode( array('status' => 'ok', 'id_partida' => $id_ruleta) );
	}
	else{
		header("Access-Control-Allow-Origin: *");
		return json_encode( array('status' => 'error', 'mensaje' => 'No puedes entrar a esta sala.') );
	}
});



// Funcion para obtener las apuestas realizadas en esa partida
Route::get('/obtener_apuestas/{id_ruleta}', function ($id_ruleta) {
	$apuestas = Ruleta::where('id', $id_ruleta)->get();
	
	header("Access-Control-Allow-Origin: *");
	return json_encode( array('status' => 'error', 'turno' => $apuestas[0]['turno'], 'datos' => $apuestas ));
});



// Funcion que se encarga de generar los numeros y insertarlos en la base de datos como string
Route::get('/numero_random/{id_partida}', function($id_partida) {
	global $arrayNumerosRuletaColores;

	// Creacion del numero random
	$numeroRandom = rand(0, 36);

	// Obtencion de los ultimos numeros
	$numeroAnteriores = Ruleta::where('id', $id_partida)->select('ultimos_numeros')->get();

	// Si hay valores entrara en el primero poniendo un guion de separacion, sino sin guion
	if( $numeroAnteriores[0]['ultimos_numeros'] == '' || $numeroAnteriores[0]['ultimos_numeros'] == null ){
		Ruleta::where('id', $id_partida)->update(['ultimos_numeros' => $numeroRandom]);
	}else{
		Ruleta::where('id', $id_partida)->update(['ultimos_numeros' => $numeroAnteriores[0]['ultimos_numeros']."-".$numeroRandom]);
	}
	
	// Ultimos 10 numeros una vez insertado el que ha sido ganador
	$numeroDespues = Ruleta::where('id', $id_partida)->select('ultimos_numeros')->get();

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('status' => 'ok', 'numeros' => $numeroDespues));
});


// Ruta que obtiene por get las variables, y crea la apuesta
Route::get('/apostar/{id_partida}/{token}/{valor}/{tipo}/{creditos}', function($id_partida, $token, $valor, $tipo, $creditos) {
	
	// Obtencion datos de la partida
	$datos_partida = Ruleta::where('id', $id_partida)->get();
	
	// Obtencion id usuario mediante token
	$usuario_actual = obtenerIdUsuario($token);

	// Si la ruleta esta en el momento de apostar...
	if ( $datos_partida[0]['estados'] == "girando"){

		// Inserción de la apuesta creada
		$nueva_apuesta = new Apuesta();
		$nueva_apuesta->creditos = $creditos;
		$nueva_apuesta->id_usuario = $usuario_actual;
		$nueva_apuesta->id_ruleta = $id_partida;
		$nueva_apuesta->ronda_ruleta = $datos_partida[0]['rondas'];
		$nueva_apuesta->tipo = $tipo;
		$nueva_apuesta->valor = $valor;
		$nueva_apuesta->save();

		// Restan los creditos al apostar
		$creditosActuales = User::where('id', $usuario_actual)->get();
		$creditosRestados = $creditosActuales[0]['creditos'] - $creditos;
		
		// Actualizacion de los datos una vez restado los creditos de apuesta
		User::where('id', $usuario_actual)->update(['creditos' => $creditosRestados]);
		$creditosActualest = User::where('id', $usuario_actual)->get();
		
		header("Access-Control-Allow-Origin: *");
		return json_encode( array(['status' => 'ok', 'mensaje' => 'Apuesta creada.']) );
	}
	else{
		header("Access-Control-Allow-Origin: *");
		return json_encode(array(['status' => 'error', 'mensaje' => 'No puedes apostar no es tu turno.']));
	}
});


// Ruta para obtener el listado 
Route::get('/salas_ruleta', function() {
	$listadoSalasRuleta = [];
	$salasRuleta = Ruleta::all();

	// For para obtener la cantidad de salas que hay de ruletas
	for($numSalas = 0; $numSalas < sizeof($salasRuleta); $numSalas++){
		$res = jugadores_sala_ruleta($salasRuleta[$numSalas]['id']);
		array_push($listadoSalasRuleta, [ 'sala' => $salasRuleta[$numSalas]['id'], 'jugadores' => $res ]);
	}

	header("Access-Control-Allow-Origin: *");
	return json_encode(array(['status' => 'ok', 'mensaje' => $listadoSalasRuleta]));
});


// Obtener todas las apuestas de la partida y ronda actual
Route::get('/printar_apuestas/{id_partida}', function($id_partida) {
	$ronda_actual= Ruleta::where('id', $id_partida)->get();
	$apuestas_partida = Apuesta::where('id_ruleta', $id_partida)->where('ronda_ruleta', $ronda_actual[0]['rondas'])->get();

	header("Access-Control-Allow-Origin: *");
	return json_encode(array(['status' => 'ok', 'mensaje' => $apuestas_partida]));
});


// Obtener todos los usuarios y sus colores, de la partida actual
Route::get('/color_usuario/{id_partida}', function($id_partida) {
	$arrayColoresUsuario = [];

	$colores = Salas_ruleta::where('id_partida', $id_partida)->get();

	// For para obtener el listado de usuarios y colores de esa sala
	for ( $num = 0; $num < sizeof($colores); $num++ ){
		array_push($arrayColoresUsuario, ['id_usuario' => $colores[$num]['id_usuario'], 'color' => $colores[$num]['color']]);
	}

	header("Access-Control-Allow-Origin: *");
	return json_encode(array(['status' => 'ok', 'mensaje' => $arrayColoresUsuario]));
});


// Funcion con los ultimos diez numeros
Route::get('/ultimos_diez_numeros/{id_partida}', function($id_partida) {
	$diez_numeros= Ruleta::where('id', $id_partida)->get();

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('status' => 'ok', 'mensaje' => $diez_numeros[0]['ultimos_numeros']));
});

/*
// Funcion de generar las ganancias, 
Route::get('/ganancias/{id_partida}/{numeroGanador}', function($id_partida, $nuermoGanador) {
	$ronda= Ruleta::where('id', $id_partida)->get();
	$res = calcularGanancias($id_partida, $ronda[0]['rondas'], $nuermoGanador);

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('status' => 'ok', 'mensaje' => 'ganancias repartidas', 'resultado' => $res));
});
*/

// Route para salir de la room actual con ese usuario
Route::get('/salirRoomRuleta/{token}', function($token){
	$id = obtenerIdUsuario($token);
	Salas_ruleta::where('id_usuario', $id)->delete();

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('status' => 'ok', 'mensaje' => 'usuario fuera.'));
});






/*
|--------------------------------------------------------------------------
| Funciones 
|--------------------------------------------------------------------------
*/

// Funcion, que devuelve el id de dicho usuario, con ese token
function obtenerIdUsuario($token){
	$id_usuario = User::where('token', $token)->get();

	header("Access-Control-Allow-Origin: *");
	return $id_usuario[0]['id'];
}

// Funcion que se encarga de añadir los movimientos a contabilidad. Se le pasara un token del usuario, y los creditos de ese turno.
function contabilidad($token, $creditos){
	// obtencion del usuario
	$usuario = obtenerIdUsuario($token);

	// insertar las perdidas en contabilidad
	if( $creditos < 0 ){
		$datos = new Contabilidad();
		$datos->id_usuario = $usuario;
		$datos->accion = "perdidas";
		$datos->creditos = $creditos;
		$datos->save();
	}
	// insertar las perdidas en contabilidad
	else if( $creditos > 0 ){
		$datos = new Contabilidad();
		$datos->id_usuario = $usuario;
		$datos->accion = "ganancias";
		$datos->creditos = $creditos;
		$datos->save();
	}
}


// funcion que devuelve el total de jugadores en esa sala
function jugadores_sala_ruleta($id_ruleta){
	//$numeroJugadoresSalaRuleta = Salas_ruleta::all()->groupBy('id_partida')->count();
	$numeroJugadoresSalaRuleta = Salas_ruleta::where('id_partida', $id_ruleta)->count();
	return $numeroJugadoresSalaRuleta;
}


// Funcion que se encarga de cambiar el estado de la partida ruleta
function cambiarEstadoRuleta($id_partida){
	// Obtencion del estado de la partida
	$estado_actual = Ruleta::where('id', $id_partida)->get();

	if( $estado_actual[0]['estado'] == "girando" ){
		Ruleta::where('id', $id_partida)->update( ['estado' => 'repartiendo' ] );
	} 
	// Si esta en repartiendo, pasara finalizada la ronda.
	else if( $estado_actual[0]['estado'] == "repartiendo" ){
		Ruleta::where('id', $id_partida)->update( ['estado' => 'girando', 'rondas' => $estado_actual[0]['rondas']+1 ] );
	}
}


// Ruta de calcular ganancias ( esta es la segunda route de ganancias )
Route::get('/ganancias2/{id_partida}/{numeroGanador}/{token}', function($id_partida, $nuermoGanador , $token) {
	$id = obtenerIdUsuario($token);
	$ronda= Ruleta::where('id', $id_partida)->select('rondas')->get();

	//$ronda[0]['rondas']
	$arrayUsuariosApostados = array();
	
	// 1 - Obtener usuarios de la partida
	$sala_partida = Salas_ruleta::where('id_partida', $id_partida)->select('id_usuario')->get();

	// Si hay usuarios en esa sala
	if( (sizeof($sala_partida) != 0) == true ){

		// busca usuario por usuario, sus apuestas
		for ($usuarios = 0; $usuarios < sizeof($sala_partida); $usuarios++){

			// mirar si han apostado
			$apuestas = Apuesta::where('id_ruleta', $id_partida)->where('ronda_ruleta', $ronda[0]['rondas'])->where('id_usuario', $sala_partida[$usuarios]['id_usuario'])->get();


			// 2- cuantas apuestas se han realizado
			if ( (sizeof($apuestas) != 0) == true ){

				if( $apuestas[0]['tipo'] == "par-impar" ){
					$arrayPar = array(2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36);
					$arrayImpar = array(1,3,5,7,9,11,13,15,17,19,21,23,29,25,27,31,33,35);

					// saber si es par y el numero tambien
					if( $apuestas[0]['valor'] % 2 == 0 && $numeroGanador % 2 == 0 ){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 2;

						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $id)->update(['creditos' => $creditosSumados]);		

						return ("PAR");
					}

					// saber si es impar y el numero tambien
					else if( $apuestas[0]['valor'] % 2 != 0 && $numeroGanador % 2 != 0 ){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 2;

						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $id)->update(['creditos' => $creditosSumados]);
						return ("IMPAR");
					}

				}
				else if( $apuestas[0]['tipo'] == "color" ){
					$arrayNegros = array(2,4,6,8,10,11,13,15,17,20,22,24,26,28,29,31,33,35);
					$arrayRojos = array(1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36);
					
					// saber si es rojo
					if( in_array($apuestas[0]['valor'], $arrayRojos) && in_array($numeroGanador, $arrayRojos) ){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 2;

						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $id)->update(['creditos' => $creditosSumados]);

						return ("ROJO");
					}

					// saber si es negro
					else if( in_array($apuestas[0]['valor'], $arrayNegros) && in_array($numeroGanador, $arrayNegros) ){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 2;
						
						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $id)->update(['creditos' => $creditosSumados]);
						return ("NEGRO");
					}
					
				}
				else if( $apuestas[0]['tipo'] == "mitad" ){
					// saber si es 1-18
					if($apuestas[0]['valor'] >= 1 && $apuestas[0]['valor'] <= 18 && $numeroGanador >= 1 && $numeroGanador <= 18){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 2;
						
						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $id)->update(['creditos' => $creditosSumados]);

						return("1 mitad");
					}

					// saber si es 19-36
					else if($apuestas[0]['valor'] >= 19 && $apuestas[0]['valor'] <= 36 && $numeroGanador >= 19 && $numeroGanador <= 36){
						
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 2;

						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $id)->update(['creditos' => $creditosSumados]);
						return ("2  mitad");
					}
					
				}
				else if( $apuestas[0]['tipo'] == "docena" ){
					// saber si es docena
					if( $apuestas[0]['valor'] == "1a" && $numeroGanador >= 1 && $numeroGanador <= 12 ){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 3;
						
						$creditosActuales = User::where('id', $apuestas[0]['id_usuario'])->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $apuestas[0]['id_usuario'])->update(['creditos' => $creditosSumados]);
					}
					
				}
				else if( $apuestas[0]['tipo'] == "columnas" ){
					$arrayColumna3 = array(3,6,9,12,15,18,21,24,27,30,33,36);
					$arrayColumna2 = array(2,5,8,11,14,17,20,23,26,29,32,35);
					$arrayColumna1 = array(1,4,7,10,13,16,19,22,25,28,31,34);
					
					// saber si es par
					if(in_array($apuestas[0]['valor'], $arrayColumna3) && in_array($numeroGanador, $arrayColumna3)) {
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 3;
						
						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $apuestas[0]['id_usuario'])->update(['creditos' => $creditosSumados]);

						return("1 col");
					}

					else if(in_array($apuestas[0]['valor'], $arrayColumna2) && in_array($numeroGanador, $arrayColumna2)) {
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 3;

						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $apuestas[0]['id_usuario'])->update(['creditos' => $creditosSumados]);
						return("2 col");
					}

					else if(in_array($apuestas[0]['valor'], $arrayColumna1) && in_array($numeroGanador, $arrayColumna1)) {
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 3;
						
						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $apuestas[0]['id_usuario'])->update(['creditos' => $creditosSumados]);
						return("3 col");
					}
					
				}
				else if( $apuestas[0]['tipo'] == "numero" ){
					if( $apuestas[0]['valor'] == $nuermoGanador ){
						// caluclar recompensa
						$recompensa = $apuestas[0]['creditos'] * 35;

						$creditosActuales = User::where('id', $id)->select('creditos')->get();
						$creditosSumados = $creditosActuales[0]['creditos'] + $recompensa;
						// obtener usuario
						User::where('id', $apuestas[0]['id_usuario'])->update(['creditos' => $creditosSumados]);
					}
				}
			}
		}
	}
	
	// Incrementacion de las rondas
	$ronda_anterior = $ronda[0]['rondas'] + 1;
	// Incrementar rondas
	Ruleta::where('id', $id_partida)->update(['rondas' => $ronda_anterior]);

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('status' => 'ok', 'mensaje' => 'ganancias repartidas'));
});



/*
|--------------------------------------------------------------------------
| Funciones BlackJack
|--------------------------------------------------------------------------
|
| A partir de aqui, se pueden observar todas las funciones que tienen que
| ver con la modalida de la blackjack.
|
|--------------------------------------------------------------------------
*/
//Funcion para crear la baraja para cada partida 


///////////////            PRUEBAS      ////////////////////////////7

//Crear una partida de blackjack
Route::get('/crearBlackjack/{turno}/{estados_bj}/{rondas}' , function( $turno , $estado , $rondas){
	$blackjack = new Blackjack();
	$blackjack->turnos = $turno ;
	$blackjack->estados = $estado;
	$blackjack->rondas = $rondas;
	$blackjack->save();
	return 'aaa';
});


//Creo una sala de blackjack
Route::get('/crearSalas/{id_usuario}/{id_partida}/{rondas}' , function( $id , $id_p , $rondas){
	$blackjack = new SalaBJ();
	$blackjack->id_usuario = $id;
	$blackjack->id_partida = $id_p;
	$blackjack->rondas = $rondas;
	$blackjack->save();

	header("Access-Control-Allow-Origin: *");
	return 'aaa';
});



//Entrar en una room de bj
Route::get('/entrar_room_bj/{id_partida}/{token}', function ($id_partida, $token) {
	$id_usuario = User::where('token' , $token)->select('id')->get();
	$id_usuario = $id_usuario[0]['id'];

	$ronda = Blackjack::where('id' , $id_partida)->select('rondas')->get();
	$ronda = $ronda[0]['rondas'];

	// numero de jugadores de la sala
	$nuemeroJugadores = jugadores_sala_bj($id_partida);

	$delete = SalaBJ::where('id_partida', '!=' , $id_partida)->where('id_usuario', $id_usuario)->get();

	if (sizeof($delete) != 0) {
		SalaBJ::where('id_partida', '!=' , $id_partida)->where('id_usuario', $id_usuario)->delete();
	}

	$infoAntes = SalaBJ::where('id_partida', $id_partida)->where('id_usuario', $id_usuario)->get();
	
	// Si en la sala hay menos de 3 usuarios, y el que se va a insertar no esta, entrara
	if ( $nuemeroJugadores < 3 && sizeof($infoAntes) == 0  ){
		//SalaBJ::where('id_usuario', $id_usuario)->delete();

		$nueva = new SalaBJ();
		$nueva->id_partida = $id_partida;
		$nueva->id_usuario = $id_usuario;
		$nueva->rondas = $ronda;
		$nueva->save();	

		header("Access-Control-Allow-Origin: *");
		return json_encode( array('status' => 'ok', 'id_partida' => $id_partida) );
	}
	else{
	header("Access-Control-Allow-Origin: *");
		return json_encode( array('status' => 'error', 'mensaje' => 'No puedes entrar a esta sala.') );
	}

});


//Funcion que te devuelve los jugadoresn de la sala
function jugadores_sala_bj($id_partida){
	//$numeroJugadoresSalaRuleta = Salas_ruleta::all()->groupBy('id_partida')->count();
	$numeroJugadoresSalaBJ = SalaBJ::where('id_partida', $id_partida)->count();
	return $numeroJugadoresSalaBJ;
}




//Funcion que te devuelve las salas de blackjack
Route::get('/salas_bj', function() {
	$listadoSalasBj = [];
	$salasBj = Blackjack::all();

	for($numSalas = 0; $numSalas < sizeof($salasBj); $numSalas++){
		$res = jugadores_sala_bj($salasBj[$numSalas]['id']);
		array_push($listadoSalasBj, [ 'sala' => $salasBj[$numSalas]['id'], 'jugadores' => $res ]);
	}

	header("Access-Control-Allow-Origin: *");
	return json_encode(array(['status' => 'ok', 'mensaje' => $listadoSalasBj]));
});


















////////////////////////////////////////////////////////////////


//Creo una baraja para cada partida creada
Route::get('/crearBaraja' , function(){
	$figuraCarta=array("corazones","picas","trevoles","diamantes");
	$contadorFigura=0;
	$contadorNumeroCarta=1;
	
	for ($cantCartas=0; $cantCartas<36; $cantCartas++) { 
		global $cartas;
		$contadorNumeroCarta++;
		$array_carta = array(['cartaNumero' => $contadorNumeroCarta,'figura' => $figuraCarta[$contadorFigura]]);
		array_push($cartas, $array_carta );

		if ($contadorNumeroCarta ==10){
			$contadorNumeroCarta=1;
			$letraCarta=array('J','Q','K','A');

			$array_carta = array(['cartaNumero' => "j",'figura' => $figuraCarta[$contadorFigura]]);
			array_push($cartas, $array_carta );
			$array_carta = array(['cartaNumero' => "q",'figura' => $figuraCarta[$contadorFigura]]);
			array_push($cartas, $array_carta );
			$array_carta = array(['cartaNumero' => "k",'figura' => $figuraCarta[$contadorFigura]]);
			array_push($cartas, $array_carta );
			$array_carta = array(['cartaNumero' => "a",'figura' => $figuraCarta[$contadorFigura]]);
			array_push($cartas, $array_carta );

			$contadorFigura++;
		}
	}

	/*
	$barajasCreadas = Carta::select('id_blackjack')->get();

	for ($i=0; $i < ; $i++) { 

		$numPartidas = BlackJack::select('id' ,'!=', $barajasCreadas[$i]['id_blackjack'])->get();
	}
	*/

	// total partidas blackjack
	$totalpartidasBJ = BlackJack::all();
	$contador = 0;
	$array = array();
	// for por cada partida de blackjack
	for ($x=0; $x <sizeof($totalpartidasBJ) ; $x++) { 

		// partidas que no tienen cartas
		$res = Carta::where('id_blackjack', $totalpartidasBJ[$x]['id'])->select('id_blackjack')->distinct()->get();
		
		//Si el size es 0 entonces esa id no existe y no tiene baraja , la creamos
		if( sizeof($res) == 0 ){

			for ($n = 0; $n < 1; $n++ ){
				for ($i = 0; $i < sizeof($cartas); $i ++) { 
				 	$figura = $cartas[$i][0]['figura'];
				 	$numero = $cartas[$i][0]['cartaNumero'];
				 	
				 	$carta = new Carta();
					$carta->figura=$figura;
					$carta->numero=$numero;
					$carta->id_blackjack=$totalpartidasBJ[$x]['id'];
					//$carta->id_usuario=0;
					$carta->save();
					//$x=sizeof($totalpartidasBJ);
				}
			}
		}
	}
});



///////////////////     REPARTIR         //////////////////

//Reparto 2 cartas a cada jugador de la partida
Route::get('/repartir/{id_blackjack}',function($idBlack){
	//Compruebo los jugadores que hay 
	$jugadores = SalaBJ::where('id_partida', $idBlack)->select('id_usuario')->get(); 
	$numCartas = sizeof($jugadores)*2+2;
	$cartass=array();	
	$cartasArray =array();
	//Cojo la baraja de esa sala
	$cartas = Carta::where('id_blackjack' , $idBlack)->select('numero' , 'figura')->get();

	//Meto la baraja en una array
	for ($i = 0; $i < sizeof($cartas) ; $i ++) {    
		 array_push($cartass, $cartas[$i]);
	}

	$cartasRdm = array_rand($cartass, $numCartas );
	shuffle($cartasRdm);

	//Cojo las cartas aleatorias pque necesito
	for ($i = 0 ; $i <sizeof($jugadores) ; $i ++) {   

		 if ($i == 0 ) {				
		 	//Cojo las dos cartas para el crupier
		 	for ($posCarta = 0; $posCarta<1 ; $posCarta ++) { 
		 	$idJugador = 0;
		 	$cartaFig = $cartass[$cartasRdm[$posCarta]]['figura'];
		 	$cartaNum = $cartass[$cartasRdm[$posCarta]]['numero'];
		 	$carta1Fig = $cartass[$cartasRdm[$posCarta+1]]['figura'];
		 	$carta1Num = $cartass[$cartasRdm[$posCarta+1]]['numero'];

		 	array_shift($cartasRdm);
		 	array_shift($cartasRdm);

		 	Carta::where('id_blackjack' , $idBlack)->where('figura' , $cartaFig)->where('numero' , $cartaNum)->update(['id_usuario'=>$idJugador]);

		 	Carta::where('id_blackjack' , $idBlack)->where('figura' , $carta1Fig)->where('numero' , $carta1Num)->update(['id_usuario'=>$idJugador]);

		 	
			//return $cartasArray;
		 	}
			
		}
		for ($posCarta = 0; $posCarta<1 ; $posCarta ++) { 	//Reparto el resto de cartas a los jugadores
		 	$idJugador = $jugadores[$i]['id_usuario'];		 	
		 	$cartaFig = $cartass[$cartasRdm[$posCarta]]['figura'];		 	
		 	$cartaNum = $cartass[$cartasRdm[$posCarta]]['numero'];
		 	$carta1Fig = $cartass[$cartasRdm[$posCarta+1]]['figura'];
		 	$carta1Num = $cartass[$cartasRdm[$posCarta+1]]['numero'];

		 	array_shift($cartasRdm);
		 	array_shift($cartasRdm);
		 	
		 	Carta::where('id_blackjack' , $idBlack)->where('figura' , $cartaFig)->where('numero' , $cartaNum)->update(['id_usuario'=>$idJugador]);

		 	Carta::where('id_blackjack' , $idBlack)->where('figura' , $carta1Fig)->where('numero' , $carta1Num)->update(['id_usuario'=>$idJugador]);

		 	

			
		 	}
		 
		}

		
		$cartasArray =array();

		for ($i=0; $i < sizeof($jugadores); $i++) { 
			$cartas = Carta:: where('id_blackjack' , $idBlack)->where('id_usuario' , $jugadores[$i]['id_usuario'])->select('figura' ,'numero')->get();

		
			$cartasArray[$jugadores[$i]['id_usuario']] = $cartas;

		}

		//return ("oli");

		$cartasC = Carta:: where('id_blackjack' , $idBlack)->where('id_usuario' , 0)->select('figura' ,'numero')->get();
		$cartasArray[0] = $cartasC;
		
		header("Access-Control-Allow-Origin: *");
		return json_encode(array(['status' => 'ok', 'cartas' => $cartasArray]));
	}
	
);





///////////         Cuenta los puntos de las cartas que se han repartido    /////////////////
Route::get('/contarPuntos/{id_blackjack}',function($id_bj){ 
	$array = contarPuntos($id_bj);
	header("Access-Control-Allow-Origin: *");
	return $array;
});


function contarPuntos ($idBlack){
	//$idBlack = 1;
	global $arrayPuntosJugadores;
	$jugadores = SalaBJ::where('id_partida', $idBlack)->select('id_usuario')->get();
	$arrayJugadores = array();

	for ($i = 0; $i < sizeof($jugadores) ; $i++) { 
		array_push($arrayJugadores, $jugadores[$i]['id_usuario']);
	}

	//Con seta variable mirare cuantas veces ha salido una A , para poder comrpobar si vale 11 o 1 
	$arrayPuntos = array();
	$contadorA = 0;										


	///////////////////////Calculo los puntos de cada jugador//////////////////
	for ($i = 0; $i <sizeof($jugadores) ; $i ++) {    //Cojo los jugadores que hay
		$puntos = 0 ;								
		$idJugador = $jugadores[$i]['id_usuario'];
		//Cojo las cartas de cada jugador
		$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , $idJugador)->select('numero' , 'figura')->get();

		//Con este for miro cuanto vale cada carta	
		for ($numCartas = 0; $numCartas <sizeof($cartas) ; $numCartas++) {  
	
			//Si es una de estas letra , la carta valdrá 10
			if ($cartas[$numCartas]['numero'] == 'j' || $cartas[$numCartas]['numero'] == 'k' || $cartas[$numCartas]['numero'] == 'q'  ) { 														
				$numero[$numCartas] = 10;
				$puntos = $puntos + $numero[$numCartas];
			//si es a valdrá 11 o 1 , dependiendo de si los puntos se pasan de 21
			}elseif ($cartas[$numCartas]['numero'] == 'a') {				
				
				$contadorA++;
				$numero[$numCartas] = 11;
				$puntos = $puntos + $numero[$numCartas];

				if ($puntos > 21) {
					$contadorA--;
					$numero[$numCartas] = 1;
					$puntos = $puntos + $numero[$numCartas] - 11;
				}
			}else{
				$numero[$numCartas] = intval($cartas[$numCartas]['numero']);		
				$puntos = $puntos + $numero[$numCartas];
			}	
		}

		//Si los puntos son mayor que 21 pero le ha salido una A , la pasará a valer 1 y le resto la diferencia
		if ($puntos > 21 && $contadorA > 0) {
			$puntos = $puntos - 10 ;
			if ($puntos > 21) {
				$arrayPuntosJugadores[$jugadores[$i]['id_usuario']] = $puntos ;
			}else{

				$arrayPuntos[$jugadores[$i]['id_usuario']] = $puntos ;

				$arrayPuntosJugadores[$jugadores[$i]['id_usuario']] = $puntos ;
			}
				
		//Si los puntos son mayor a 21 y no tiene ninguna A , lo elimino no metiendolo en la lista
		}elseif ($puntos > 21 ){
			$arrayPuntosJugadores[$jugadores[$i]['id_usuario']] = $puntos ;
			
		}else{
			$arrayPuntos[$jugadores[$i]['id_usuario']] = $puntos ;
			$arrayPuntosJugadores[$jugadores[$i]['id_usuario']] = $puntos ;
		}
	}


		/////////////Aqui calcula los puntos de la maquina //////////////////
		$contadorA = 0;
		$puntos = 0 ;	
		//return $idJugador;
		//Cojo las cartas de cada jugador
		$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , 0)->select('numero' , 'figura')->get();
		
		//Con este for miro cuanto vale cada carta
		for ($numCartas = 0; $numCartas <sizeof($cartas) ; $numCartas++) {  
			//Si es una de estas letra , la carta valdrá 10
			if ($cartas[$numCartas]['numero'] == 'j' || $cartas[$numCartas]['numero'] == 'k' || $cartas[$numCartas]['numero'] == 'q'  ) { 														
				$numero[$numCartas] = 10;
				$puntos = $puntos + $numero[$numCartas];
			//si es a valdrá 11 o 1 , dependiendo de si los puntos se pasan de 21
			}else if ($cartas[$numCartas]['numero'] == 'a') {
				$contadorA++;				
				$numero[$numCartas] = 11;
				$puntos = $puntos + $numero[$numCartas];

				if ($puntos > 21) {
					$numero[$numCartas] = 1;
					$puntos = $puntos + $numero[$numCartas] - 11;
				}
			}else{

				$numero[$numCartas] = intval($cartas[$numCartas]['numero']);		
				$puntos = $puntos + $numero[$numCartas];
			}
		}

		if ($puntos > 21 && $contadorA > 0) {						
			$puntos = $puntos - 10 ;
			$arrayPuntos[0] = $puntos ;	
			$arrayPuntosJugadores[0] = $puntos ;	

		}elseif ($puntos > 21 ){
			$arrayPuntosJugadores[0] = $puntos ;

		}else{
			$arrayPuntos[0] = $puntos ;	

			$arrayPuntosJugadores[0] = $puntos ;

		}

	header("Access-Control-Allow-Origin: *");
	return $arrayPuntos;
}


//////////////////         QUITAR CARTAS  DE LA PARTIDA    //////////////////////
Route::get('/quitarCartas/{id_blackjack}',function($id_bj){ 
	Carta::where('id_blackjack' , $id_bj)->update(['id_usuario'=>null]);
	header("Access-Control-Allow-Origin: *");
	return 'done0';
});


function quitarCartas($id_bj){
	Carta::where('id_blackjack' , $id_bj)->update(['id_usuario'=>null]);	//Funcion para quitar todas las cartas a los jugadores
}


/////////////////         PASA TURNO Y AUMENTO 1 RONDA DE JUGADOR      ////////////////7
Route::get('/pasa/{id_blackjack}/{token}/{ronda}',function($id_bj , $token , $ronda){ 	
	$pasa = pasa($id_bj , $token , $ronda);
	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado' => 'pasa'));
});


function pasa ($idBlack , $token , $ronda){
	$idJugador = User::where('token' , $token)->select('id')->get();
	
	$rondaActual = $ronda+1;

	SalaBJ::where('id_partida' , $idBlack)->where('id_usuario' , $idJugador[0]['id'])->update(['rondas'=> $rondaActual]);

	$jugadores = listaJugadores($idBlack , $ronda);

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado' => 'pasa'));
	
	
}

// LA IA PASA TURNO 
function pasaIA ($idBlack  , $ronda){
	$idJugador = User::where('token' , $token)->select('id')->get();
	
	$rondaActual = $ronda+1;

	SalaBJ::where('id_partida' , $idBlack)->where('id_usuario' , $idJugador[0]['id'])->update(['rondas'=> $rondaActual]);

	$jugadores = listaJugadores($idBlack , $ronda);
	header("Access-Control-Allow-Origin: *");
	
}






/////////////////         LISTA CON LOS IDS DE LOS JUGADORES         /////////////////////////

Route::get('/listaJugadores/{id_blackjack}/{ronda}',function($id_bj ,  $ronda){ 	
	$listaJugadores =listaJugadores($id_bj ,  $ronda);
	$lengthLista = sizeof($listaJugadores);

	header("Access-Control-Allow-Origin: *");
	
	return json_encode(array('estado'=>'ok' , 'listaJugadores' =>$listaJugadores , 'lengthLista' => $lengthLista));
});


function listaJugadores ($idBlack , $ronda){	
	$jugadores = SalaBJ::where('id_partida', $idBlack)->where('rondas' , $ronda)->select('id_usuario')->get();
	$arrayJugadores = array();

	for ($i = 0; $i < sizeof($jugadores) ; $i++) { 
		array_push($arrayJugadores, $jugadores[$i]['id_usuario'] );
		//array_push($arrayJugadores, array('id' =>  $jugadores[$i]['id_usuario']) );
	}

	return $arrayJugadores;
}



//////////////          PIDE UNA CARTA MAS        ////////////////////////////

Route::get('/pide/{id_blackjack}/{token}/{ronda}',function($id_bj , $token , $ronda){ 	
	$cartas = pedirCarta($id_bj ,$token , $ronda);

	header("Access-Control-Allow-Origin: *");
	return ($cartas);
});


function pedirCarta ($idBlack , $token , $ronda){
	$idJugador = User::where('token' , $token)->select('id')->get();
	$idJugador = $idJugador[0]['id'];

	$rondaJugador = SalaBJ::where('id_partida' , $idBlack)->select("rondas")->get();

	if ($rondaJugador[0]['rondas'] != $ronda) {
		return json_encode(array('estado'=>'pasa1'));
	}
	$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , null)->select('numero' , 'figura')->get();
	$cartass = array();

	for ($i = 0; $i < sizeof($cartas) ; $i ++) {    //Meto la baraja en una array
		 array_push($cartass, $cartas[$i]);
	}

	shuffle($cartass);
	$cartasRdm = array_rand($cartass, 1 );
	$cartaFig = $cartass[$cartasRdm]['figura'];		 	
	$cartaNum = $cartass[$cartasRdm]['numero'];
	
	Carta::where('id_blackjack' , $idBlack)->where('figura' , $cartaFig)->where('numero' , $cartaNum)->update(['id_usuario'=>$idJugador]);

	$listaPuntos = contarPuntos($idBlack);
	if (isset($listaPuntos[$idJugador])) {

		return json_encode(array('estado'=>'pide' , 'figura' =>$cartaFig , 'numero' => $cartaNum));
		
	}else{
		$pasa = pasa($idBlack , $token , $ronda);
		return json_encode(array('estado'=>'pasa' , 'figura' =>$cartaFig , 'numero' => $cartaNum ));
	}
		 	
	
}

//LA IA PIDE UNA CARTA
function pedirCartaIA ($idBlack  , $ronda){
	$idJugador = 0;
	
	$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , null)->select('numero' , 'figura')->get();
	$cartass = array();

	for ($i = 0; $i < sizeof($cartas) ; $i ++) {    //Meto la baraja en una array
		 array_push($cartass, $cartas[$i]);
	}

	shuffle($cartass);
	$cartasRdm = array_rand($cartass, 1 );
	$cartaFig = $cartass[$cartasRdm]['figura'];		 	
	$cartaNum = $cartass[$cartasRdm]['numero'];
	
	Carta::where('id_blackjack' , $idBlack)->where('figura' , $cartaFig)->where('numero' , $cartaNum)->update(['id_usuario'=>$idJugador]);

	$listaPuntos = contarPuntos($idBlack);
	if (isset($listaPuntos[$idJugador])) {

		return json_encode(array('estado'=>'pide' , 'figura' =>$cartaFig , 'numero' => $cartaNum));
		
	}else{
		//listaGanadores($idBlack , $ronda);
		aumentarRonda($idBlack);
		return json_encode(array('estado'=>'pasa' , 'figura' =>$cartaFig , 'numero' => $cartaNum ));
	}
		 	
	
}



////////////////////////      DOBLAS LA APUESTA , PIDES UNA CARTA MAS Y TE PLANTAS     //////////////////////////
Route::get('/dobla/{id_blackjack}/{token}/{rondaJugador}',function($id_bj , $token , $rondaJugador){ 	
	$doble = doblar($id_bj, $token, $rondaJugador);
	header("Access-Control-Allow-Origin: *");
	return $doble;
});


function doblar ($idBlack , $token , $rondaJugador){
	$idJugador = User::where('token' , $token)->select('id')->get();
	$idJugador = $idJugador[0]['id'];
	$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , null)->select('numero' , 'figura')->get();
	$cartass = array();

	for ($i = 0; $i < sizeof($cartas) ; $i ++) {    //Meto la baraja en una array
		 array_push($cartass, $cartas[$i]);
	}

	shuffle($cartass);
	$cartasRdm = array_rand($cartass, 1 );
	$cartaFig = $cartass[$cartasRdm]['figura'];		 	
	$cartaNum = $cartass[$cartasRdm]['numero'];

	Carta::where('id_blackjack' , $idBlack)->where('figura' , $cartaFig)->where('numero' , $cartaNum)->update(['id_usuario'=>$idJugador]);

	$apuesta = ApuestasBj::where('id_bj' , $idBlack)->where('ronda_bj' , $rondaJugador)->where('id_usuario' , $idJugador)->select('creditos')->get();

	$creditos_user = User::where('id' ,$idJugador)->select('creditos')->get();
	$creditos_user = $creditos_user[0]['creditos']-$apuesta[0]['creditos'];

	User::where('id' , $idJugador)->update(['creditos'=>$creditos_user]);
	
	$apuesta = $apuesta[0]['creditos']*2;
	ApuestasBj::where('id_bj' , $idBlack)->where('ronda_bj' , $rondaJugador)->where('id_usuario' , $idJugador)->update(['creditos'=>$apuesta]);

	$listaPuntos = contarPuntos($idBlack);
	if (isset($listaPuntos[$idJugador])) {
		header("Access-Control-Allow-Origin: *");
		$pasa = pasa($idBlack , $token , $rondaJugador);
		return json_encode(array('estado'=>'pide' , 'figura' =>$cartaFig , 'numero' => $cartaNum));
		
	}else{
		$pasa = pasa($idBlack , $token , $rondaJugador);
		header("Access-Control-Allow-Origin: *");
		return json_encode(array('estado'=>'pasa' , 'figura' =>$cartaFig , 'numero' => $cartaNum));
	}

}



/////////////////////    APOSTAR    /////////////////////////////

Route::get('/apostarBJ/{id_blackjack}/{token}/{ronda}/{creditos}',function($idBlack , $token , $ronda , $creditos){ 	
	apostar($idBlack , $token , $ronda , $creditos);
	header("Access-Control-Allow-Origin: *");
	return 'donete';
	
});


function apostar ($idBlack , $token , $ronda , $creditos){
	$idJugador = User::where('token' , $token)->select('id')->get();
	$idJugador = $idJugador[0]['id'];
	$apuesta = new ApuestasBJ();
	$apuesta->id_bj = $idBlack;
	$apuesta->ronda_bj = $ronda;
	$apuesta->id_usuario = $idJugador;
	$apuesta->creditos = $creditos;
	$apuesta->save();

	$creditos_user = User::where('id' ,$idJugador)->select('creditos')->get();

	$creditos_user = $creditos_user[0]['creditos']-$creditos;
	User::where('id' , $idJugador)->update(['creditos'=>$creditos_user]);
	
	header("Access-Control-Allow-Origin: *");
	return 'donete';
}



/////////////////     LISTA DE GANADORES PARA REPARTIR PREMIOS         ///////////////////////7

Route::get('/listaG/{id_blackjack}/{ronda}',function($idBlack ,  $ronda ){ 	
	listaGanadores($idBlack , $ronda);
	header("Access-Control-Allow-Origin:"); 
	return json_encode(array('estado'=>'ok'));
});

function listaGanadores ($id_bj, $ronda ){
	//$jugadores = listaJugadores($id_bj , $rondaJugador);
	$jugadores = 1; // fuerzo el 1 para la demo!!
	$arrayPuntos = contarPuntos($id_bj);
	//$rondaPartida = $rondaJugador -1 ;

	;

	global $arrayPuntosJugadores;

	//return$arrayPuntosJugadores;

	if (isset($arrayPuntos[0])) {
		if (isset($arraPuntos[1])) {
			if ($arrayPuntos[1] > $arrayPuntos[0]) {
				$creditosGanados = ApuestasBj::where('id_bj' , $id_bj)->where('id_usuario' , $jugadores )->where('ronda_bj' , $ronda)->select('creditos')->get();

				$creditosGanados = $creditosGanados[0]['creditos'] * 2;

				$creditosActuales = User::where('id' , 1)->select('creditos')->get();

				$creditosTotales = $creditosActuales+ $creditosGanados;

				User::where('id' , 1)->update(['creditos'=>$creditosTotales]);
			}else if ($arrayPuntos[1] == $arrayPuntos[0]){
				$creditosGanados = ApuestasBj::where('id_bj' , $id_bj)->where('id_usuario' , $jugadores)->where('ronda_bj' , $ronda)->select('creditos')->get();

				$creditosActuales = User::where('id' , 1)->select('creditos')->get();

				$creditosTotales = $creditosActuales + $creditosGanados[0]['creditos'];

				User::where('id' , 1)->update(['creditos'=>$creditosTotales]);

			}else{
				//Pierde
			}
		}else{
			//Pierde
		}
		
	}else{
		$creditosGanados = ApuestasBj::where('id_bj' , $id_bj)->where('id_usuario' , $jugadores)->where('ronda_bj' , $ronda)->select('creditos')->get();

				$creditosGanados = $creditosGanados[0]['creditos'] * 2;

				$creditosActuales = User::where('id' , 1)->select('creditos')->get();

				$creditosTotales = $creditosActuales[0]['creditos']+ $creditosGanados;

				User::where('id' , 1)->update(['creditos'=>$creditosTotales]);

	}

	return "Bien";
}


// CUENTO EL NUMERO DE CARTAS QUE TIENE EL JUGADOR
Route::get('/contarCartas/{id_blackjack}/{token}',function($idBlack , $token ){ 	
	$idJugador = User::where('token' , $token)->select('id')->get();
	$idJugador = $idJugador[0]['id'];
	$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , $idJugador)->count();


	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado'=>'ok' , 'numCartas' =>$cartas , 'idJugador' => $idJugador));
});

// CUENTO EL NUMERO DE CARTAS DE LA IA
Route::get('/contarCartasIa/{id_blackjack}',function($idBlack  ){ 	
	$idJugador = 0;
	$cartas = Carta::where('id_blackjack' , $idBlack)->where('id_usuario' , $idJugador)->count();


	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado'=>'ok' , 'numCartas' =>$cartas , 'idJugador' => $idJugador));
});


// TE DEVUELVE LA RONDA ACTUAL DE LA PARTIDA
Route::get('/rondaActual/{id_blackjack}',function($idBlack){ 	
	$rondaActual = rondaActual($idBlack );
	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado'=>'ok' , 'rondaActual' =>$rondaActual));
});


function rondaActual($idBlack){
	$rondaActual = BlackJack::where('id' , $idBlack)->select('rondas')->get();
	return $rondaActual[0]['rondas'];
}

// TE DEVUELVE EL ID DE LA PARTIDA
Route::get('/idPartida/{token}',function($token){ 		
	$idBlackjack = idBlackjack($token);

	$estadoPartida = estadoPartida($idBlackjack);
	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado'=>'ok' , 'idPartida' =>$idBlackjack , 'estadoPartida' => $estadoPartida));
});


function idBlackjack($token){
	$idJugador = User::where('token' , $token)->select('id')->get();
	$idJugador = $idJugador[0]['id'];
	
	
	$idBlackjack = SalaBJ::where('id_usuario' , $idJugador)->select('id_partida')->get();
	return $idBlackjack[0]['id_partida'];
	
}

// TE DEVUELVE LA RONDA DEL JUGADOR
Route::get('/rondaJugador/{id_blackjack}/{token}',function($idBlack , $token){ 	
	$rondaJugador = rondaJugador($idBlack , $token);
	
	header("Access-Control-Allow-Origin: *");
	return json_encode(array('estado'=>'ok' , 'rondaJugador' =>$rondaJugador));
});


function rondaJugador($idBlack , $token){
	$idJugador = User::where('token' , $token)->select('id')->get();
	$idJugador = $idJugador[0]['id'];
	$rondaJugadores = SalaBJ::where('id_partida' , $idBlack)->where('id_usuario' , $idJugador)->select('rondas')->get();
	
	return $rondaJugadores[0]['rondas'];
}

// AUMENTO LA RONDA DEL JUGADOR
function aumentarRondaJugador($idBlack ){
	$rondaJugadores = SalaBJ::where('id_partida' , $idBlack)->select('rondas')->get();
	$rondaJugador = $rondaJugadores[0]['rondas'] +1;
	
}
// AUMENTO LA RONDA DE LA PARTIDA

function aumentarRonda ($idBlack){
	$rondaActual = BlackJack::where('id' , $idBlack)->select('rondas')->get();
	$rondaActual = $rondaActual[0]['rondas'] + 1;
	BlackJack::where('id' , $idBlack)->update(['rondas' => $rondaActual]);
}

// TE DEVUELVE EL ESTADO DE LA PARTIDA
function estadoPartida ($idBlack){
	$estadoPartida = BlackJack::where('id' , $idBlack)->select('estados')->get();

	return $estadoPartida[0]['estados'];
	
}

//DEVUELVE LOS PUNTOS DEL JUGADOR
Route::get('/puntos/{id_blackjack}', function($idBlack){ 

	$puntos = puntos($idBlack , 1);

	header("Access-Control-Allow-Origin: *");
	return $puntos; 
});

//DEVUELVE LOS PUNTOS DE LA IA
Route::get('/puntosIA/{id_blackjack}', function($idBlack){ 

	$puntos = puntos($idBlack , 0);

	header("Access-Control-Allow-Origin: *");
	return $puntos; 
});


function puntos ($idBlack , $id){
	global $arrayPuntosJugadores;
	contarPuntos($idBlack);



	if (isset($arrayPuntosJugadores[$id])) {
		return json_encode(array('estado'=>'ok' , "puntos" => $arrayPuntosJugadores[$id]));
		
	}else {
		return json_encode(array('estado'=>'no'));
	}
}

//AUMENTA EL ESTADO DE LA PARTIDA

function aumentarEstadoPartida ($idBlack){
	$estadoPartida = BlackJack::where('id' , $idBlack)->select('estados')->get();

	$estadoPartida = $estadoPartida[0]['estados'] +1 ;

	//return $estadoPartida;
	
}


//DICE SI ES EL TURNO DE LA IA O NO
Route::get('/turnos/{id_blackjack}/{listaLength}/{ronda}', function($idBlack , $listaLength , $ronda){ 

	$contador = SalaBJ::where("id_partida" , $idBlack)->where("rondas" ,'>', $ronda)->count();

	if ($contador == $listaLength) {
		header("Access-Control-Allow-Origin: *");
		return json_encode(array('estado'=>'ia' , "contador" => $contador));
	}else{
		header("Access-Control-Allow-Origin: *");
		return json_encode(array('estado'=>'ok' , "contador" => $contador));
	}
});


// SALIR DE LA ROOM DE BLACKJACK
Route::get('/salirRoomBj/{token}', function($token){
	$id = obtenerIdUsuario($token);

	SalaBJ::where('id_usuario', $id)->delete();

	header("Access-Control-Allow-Origin: *");
	return json_encode(array('status' => 'ok', 'mensaje' => 'usuario fuera.'));
});



//INTELIGENCIA ARTIFICIAL
Route::get('/ia/{id_blackjack}/{rondaJugador}', function($idBlack , $rondaJugador ){
    $cartasJugadores = contarPuntos($idBlack);

    $jugadores = SalaBJ::where('id_partida', $idBlack)->select('id_usuario')->get();
	$arrayJugadores = array();
	$CartasIA = 0;

	for ($i = 0; $i < sizeof($jugadores) ; $i++) { 
		array_push($arrayJugadores, $jugadores[$i]['id_usuario']);
	}

    $listaJugadores = listaJugadores($idBlack , $rondaJugador);
    
    
    //La puntuacion de los jugadores que hay en la mesa
    if (isset($cartasJugadores[0])) {
    	$CartasIA = $cartasJugadores[0];
    }
    
    //return $CartasIA;	//La puntuacion de la máquina
    //return sizeof($cartasJugadores);
    $contadorJuagadores = sizeof($cartasJugadores)-1 ;  //Contador de los jugadores que hay en la mesa , le resto uno que es 															la maquina
    //Jugadores a los que la maquina supera en puntos
    $jugadoresSuperados =  0;   
    //Jugadores a los que la maquina NO supera en  puntos                        
    $jugadoresNoSuperados = 0;  


    

    //return $cartasJugadores[1];                       

    //Si solo hay un jugador en la mesa...
    if ($contadorJuagadores == 1) {                            
        //Compruebo los puntos del jugador
        for ($i = 0; $i < $contadorJuagadores ; $i ++) {     
        	//Si no , pide otra
        	//return $i;

        	if (isset($cartasJugadores[$arrayJugadores[$i]])) {
     			if ($CartasIA < $cartasJugadores[$arrayJugadores[$i]]) { 
            	
					$carta = pedirCartaIA ($idBlack  , $rondaJugador);
					header("Access-Control-Allow-Origin: *");
					return $carta;
					//return json_encode(array('estado'=>'fin'));
	            }else{                                        
		          	//Si los puntos de la maquina son igual o superiores a los del jugador se planta
		            //listaGanadores($idBlack , $rondaJugador);
		            aumentarRonda($idBlack);
		            //quitarCartas($idBlack);
		            header("Access-Control-Allow-Origin: *");
		            return json_encode(array('estado'=>'fin')); 
		            }  
     		}else{
     			aumentarRonda($idBlack);
		        //quitarCartas($idBlack);
		        header("Access-Control-Allow-Origin: *");
		        return json_encode(array('estado'=>'fin')); 

     		} 
            
    	}
    }elseif ($contadorJuagadores == 0) {    	
    	aumentarRonda($idBlack);
	    //quitarCartas($idBlack);
	    header("Access-Control-Allow-Origin: *");
    	return json_encode(array('estado'=>'fin'));
    }/*
    else if ($contadorJuagadores == 2) {                    
    	//Cuando hay 2 jugadores en la mesa
        for ($i = 0; $i < $contadorJuagadores ; $i ++) {     
        	//Compruebo por order los puntos de los jugadores , primero un jugador y luego otro
            if ($CartasIA >= $cartasJugadores[$i]) {        
            	//Si las cartas de ese jugador son iguales o inferiores a las de la maquina , aumento la variable jugadoresSuperados  
                $jugadoresSuperados++;
            }else{
                //Si las cartas de ese jugador NO son iguales o inferiores a las de las      maquina , aumento la variable jugadoresNoSuperados
                $jugadoresNoSuperados++;
            }
        }
            
            if ($jugadoresSuperados > $jugadoresNoSuperados || $jugadoresSuperados == $jugadoresNoSuperados && $CartasIA >= 17) {
                //Si el numero de jugadoresSuperados es mayor al de jugadoresNoSuperados O es igual y el valor de las cartas de la maquina es mayor o igual a 17 ME PLANTO
                listaGanadores($idBlack , $rondaJugador);
                quitarCartas($idBlack);
            }else{
                while($jugadoresSuperados < $jugadoresNoSuperados || $jugadoresSuperados == $jugadoresNoSuperados && $CartasIA < 17){
                pedirCarta ($idBlack , $idJugador , $rondaJugador);
                }
            }
        }
    else if ($contadorJuagadores == 3){                        //Cuando hay 3 jugadores compruebo los puntos de las misma manera que                                                             cuando hay 2
        for ($i = 0; $i < $contadorJuagadores ; $i ++) { 

            if ($CartasIA >= $cartasJugadores[$i]) {
                
                $jugadoresSuperados++;

            }else{ 
                $jugadoresNoSuperados++;
            }
        }
        if ($jugadoresSuperados > $jugadoresNoSuperados || $jugadoresSuperados == $jugadoresNoSuperados-1 && $CartasIA >= 17){
                //Aqui lo que cambia es que cuando el nombre de jugadoresSuperados es 1 menos que el de jugadoresNoSuperados () y el nombre de puntros es igual o mayor a 17 me planto
            listaGanadores($idBlack , $ronda);
            quitarCartas($idBlack);

        }else{
            while($jugadoresSuperados < $jugadoresNoSuperados || $jugadoresSuperados == $jugadoresNoSuperados-1 && $CartasIA < 17){
                pedirCarta ($idBlack , $idJugador , $ronda);
            }
        }*/
    
});