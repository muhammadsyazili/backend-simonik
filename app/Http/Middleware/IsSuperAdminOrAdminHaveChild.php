<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\LevelOnlySlug;

class IsSuperAdminOrAdminHaveChild
{
    use \App\Traits\ApiResponser;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail(request()->header('X-User-Id'));
        if ($user->role->name === 'super-admin') {
            return $next($request);
        } else {
            $childLevels = Arr::flatten(LevelOnlySlug::with('childsRecursive')->where(['id' => $user->unit->level->id])->get()->toArray());
            return count($childLevels) > 1 ? $next($request) : $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null);
        }
    }
}
