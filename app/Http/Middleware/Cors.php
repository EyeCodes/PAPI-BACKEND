<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $allowedOrigins = [
            // 'http://127.0.0.1:8000',
            // 'http://192.168.1.9:8000',
            '*'
        ];

        $origin = $request->headers->get('Origin');

        if($request->isMethod('OPTIONS')){
            $response = response()->noContent();
        } else{
            $response = $next($request);
        }

        if(in_array($origin, $allowedOrigins)){
             $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');


        // return $next($request);
        return $response;
    }
}
