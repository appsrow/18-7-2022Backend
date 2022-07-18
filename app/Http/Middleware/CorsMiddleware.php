<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
		//Intercepts OPTIONS requests
		/*if($request->isMethod('OPTIONS')) {
			$response = response('', 200);
		} else {
			// Pass the request to the next middleware
			$response = $next($request);
		}*/

		/*print_r($request);
		exit;*/

		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
			// may also be using PUT, PATCH, HEAD etc
			//$response->header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			$response->header('Access-Control-Allow-Methods', 'HEAD, OPTIONS, GET, POST, PUT, PATCH, DELETE');
			
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
			$response->header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
			
			//exit(0);
		} else {
			// Pass the request to the next middleware
			$response = $next($request);
		}
		
		// Adds headers to the response
		
		//$response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
		//$response->header('Access-Control-Allow-Origin', url('/'));
		//$response->header('Access-Control-Allow-Origin', '*');
		//$response->header('Access-Control-Max-Age', '600');

		$response->header('Access-Control-Allow-Origin', '*');
		//$response->header('Access-Control-Allow-Origin', 'http://localhost:4200');
		$response->header('Access-Control-Allow-Headers', '*');
		$response->header('Access-Control-Max-Age', '600');

		// Sends it
		return $response;
	}
}