<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Lang;

class ValidJsonMiddleware
{
    public $selected_language;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->selected_language = $request->header('Selected-Language', 'es');

        // Attempt to decode payload
        json_decode($request->getContent());

        if (json_last_error() != JSON_ERROR_NONE) {
            // There was an error
            return response()->json([
                'success' => 'false',
                'message' => Lang::get('auth.invalid_json', array(), $this->selected_language)
            ], 400);
            //abort(400, 'Bad JSON received');
        }

        // JSON decoding didn’t throw error; continue
        return $next($request);
    }
}
