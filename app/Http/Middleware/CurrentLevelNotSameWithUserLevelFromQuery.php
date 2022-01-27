<?php

namespace App\Http\Middleware;

use App\Repositories\IndicatorRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\UserRepository;

class CurrentLevelNotSameWithUserLevelFromQuery
{
    use \App\Traits\ApiResponser;

    private UserRepository $userRepository;
    private IndicatorRepository $indicatorRepository;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->indicatorRepository = new IndicatorRepository();
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
            //return $request->query('level') === $user->unit->level->slug ? $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null) : $next($request);
            return $request->level === $user->unit->level->slug ? $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null) : $next($request);
        }
    }
}
