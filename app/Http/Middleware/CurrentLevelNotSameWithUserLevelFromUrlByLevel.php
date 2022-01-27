<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\UserRepository;

class CurrentLevelNotSameWithUserLevelFromUrlByLevel
{
    use \App\Traits\ApiResponser;

    private UserRepository $userRepository;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        if ($user->role->name === 'super-admin') {
            return $next($request);
        } else {
            return $request->level === $user->unit->level->slug ? $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null) : $next($request);
        }
    }
}
