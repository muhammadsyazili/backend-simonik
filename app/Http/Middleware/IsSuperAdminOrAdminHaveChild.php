<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\LevelRepository;
use App\Repositories\UserRepository;

class IsSuperAdminOrAdminHaveChild
{
    use \App\Traits\ApiResponser;

    private UserRepository $userRepository;
    private LevelRepository $levelRepository;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->levelRepository = new LevelRepository();
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
        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));
        if ($user->role->name === 'super-admin') {
            return $next($request);
        } else if ($user->role->name === 'admin') {
            $childs = Arr::flatten($this->levelRepository->find__allSlug__with__this_childs__by__id($user->unit->level->id));

            return count($childs) > 1 ?
            $next($request) :
            $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null);
        } else {
            return $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null);
        }
    }
}
