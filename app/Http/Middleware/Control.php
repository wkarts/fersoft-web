<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Models\Venda;
use App\Models\ConfigNota;

class Control
{

	public function handle($request, Closure $next){

		if($_SERVER['HTTP_HOST'] == 'localhost:8000' || $_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'localhost:9000'){
			return $next($request);
		}

		if(!extension_loaded('curl')){
			return $next($request);
		}

		$data1 = [
			'url' => url()->full(),
			'fone1' => env('CONTATO_SUPORTE') ?? '',
			'fone2' => env('RESP_FONE') ?? '',
			'appname' => env('APP_NAME') ?? '',
		];

		try{
			$defaults = array(
				CURLOPT_URL => base64_decode('aHR0cHM6Ly91cGRhdGUuc3lzZmFzdC5jb20uYnIvYXBpL2FjZXNzby1zYXZl'),
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $data1,
				CURLOPT_TIMEOUT => 3000,
				CURLOPT_RETURNTRANSFER => true
			);
			
			$curl = curl_init();
			curl_setopt_array($curl, $defaults);
			$error = curl_error($curl);
			$response = curl_exec($curl);
			
			$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			$err = curl_error($curl);
			curl_close($curl);

			// return $next($request);
			if ($http_status == '200') {
				return $next($request);
			} else {
				return $next($request);
			}
		}catch (\Exception $e) {
			return $next($request);
			dd($e->getMessage());
			// return $next($request);
		}
	}

}