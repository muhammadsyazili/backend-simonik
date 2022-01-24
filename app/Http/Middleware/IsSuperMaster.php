<?php

namespace App\Http\Middleware;

use App\Repositories\IndicatorRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuperMaster
{
    use \App\Traits\ApiResponser;

    private IndicatorRepository $indicatorRepository;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
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
        return $this->indicatorRepository->findLabelById($request->id) === 'super-master' ? $next($request) : $this->APIResponse(false, Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED], null, null);
    }
}
