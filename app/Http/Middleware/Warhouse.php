<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Warhouse
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if(!$user){
            $user = User::where('phone_number', request()->phone_number)->first();
        }
        
        if($user && $user->id != 1) {
            return response(['Status' => 'Failed', 'Error' =>'Not allowed.'], 401);
        }

        return $next($request);
    }
}
