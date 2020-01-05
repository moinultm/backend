<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Collection;

class RoleMiddleware
{

    public function handle($request, Closure $next, ...$roles)
    {
        if ($request->user() == null) {
            return response()->json(['error' => 'unauthorized', 'message' => 'The request does not contains token'], 401);
        }
        $this->hasRole($request->user(), $roles);
        return $next($request);
    }


    private function hasRole($user, array $roles): bool
    {
        $roles = collect($roles);
        switch ($roles->first()) {
            case 'any':
                $roles->forget(0);
                return $this->any($user, $roles);
            case 'all':
                $roles->forget(0);
                return $this->all($user, $roles);
            default:
                return $this->any($user, $roles);
        }
    }

    private function any($user, Collection $roles): bool
    {
        $result = false;
        foreach ($user->profiles as $profile) {
            $result = $result || $profile->roles->pluck('code')->intersect($roles)->count() != 0;
        }
        return $result;
    }


    private function all($user, Collection $roles): bool
    {
        $results = collect();
        foreach ($user->profiles as $profile) {
            $intersection = $profile->roles->pluck('code')->intersect($roles);
            foreach ($intersection as $item) {
                if (!$results->contains($item)) {
                    $results->push($item);
                }
            }
        }
        dd($results->count() == $roles->count());
        return $results->count() == $roles->count();
    }
}
