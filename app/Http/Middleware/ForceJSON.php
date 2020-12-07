<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


// Laravel will default to providing html responses to errors if the client allows this
// for this application json is always preferred
// taken from https://gist.github.com/meSingh/c90c5abc81a9cc0687a62a84eb2c696b
class ForceJSON
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
