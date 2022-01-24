<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\UserRepository;

class IsSuperAdmin
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
        $user = $this->userRepository->findWithRoleById($request->header('X-User-Id'));

        return $user->role->name === 'super-admin' ?
        $next($request) :
        $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null);
    }
}
