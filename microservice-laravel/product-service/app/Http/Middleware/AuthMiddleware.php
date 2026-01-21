<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if(!$token){
            return response()->json(['message' => "Token Missing", 'success' => false], 401);
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($token)->withHeaders([
            'Host' => 'auth.localhost' //isi hostnya dengan DNS local
        ])->get("http://nginx/api/verify"); //package http tidak bisa membaca DNS local jadi ubah seperti ini
        
        $json = $response->json();
        if($json['valid']){
            return $next($request);
        }
        
        return response()->json(['message' => "Token tidak Valid", 'success' => false], 401);
    }
}
