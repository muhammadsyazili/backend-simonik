<?php

namespace App\Http\Controllers\Extends\Target;

use App\DTO\ConstructRequest;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Indicator;
use App\Models\User;
use App\Models\Level;
use App\Models\Unit;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\LevelIsThisAndChildFromUserRole;
use App\Rules\UnitIsThisAndChildUserRole;
use App\Rules\UnitMatchOnRequestLevel;
use App\Services\TargetPaperWorkService;
use App\Services\TargetPaperWorkValidationService;

class PaperWorkTargetController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function editOld(Request $request)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new LevelIsThisAndChildFromUserRole($user)],
            'unit' => ['required', 'string', new UnitIsThisAndChildUserRole($user), new UnitMatchOnRequestLevel($request->query('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        if ($validator->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        $isSuperAdmin = $user->role->name === 'super-admin';

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work target (Level: %s) (Unit: %s) (Tahun: %s) showed", $request->query('level'), $request->query('unit'), $request->query('tahun')),
            [
                'levels' => $isSuperAdmin ?
                    Level::with('childsRecursive')->where(['parent_id' => Level::firstWhere(['slug' => 'super-master'])->id])->get() :
                    Level::with('childsRecursive')->where(['parent_id' => $user->unit->level->id])->get(),
                'indicators' => Indicator::with(['targets', 'realizations', 'childsHorizontalRecursive'])
                    ->referenced()
                    ->rootHorizontal()
                    ->where(
                        [
                            'level_id' => Level::firstWhere(['slug' => $request->query('level')])->id,
                            'label' => $request->query('unit') === 'master' ? 'master' : 'child',
                            'unit_id' => $request->query('unit') === 'master' ? null : Unit::firstWhere(['slug' => $request->query('unit')])->id,
                            'year' => $request->query('tahun')
                        ]
                    )
                    ->get(),
            ],
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $userRepository = new UserRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;

        $targetPaperWorkValidationService = new TargetPaperWorkValidationService($constructRequest);

        $validation = $targetPaperWorkValidationService->editValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $targetPaperWorkService = new TargetPaperWorkService($constructRequest);

        $userId = $request->header('X-User-Id');
        $level = $request->query('level');
        $unit = $request->query('unit');
        $year = $request->query('tahun');

        $response = $targetPaperWorkService->edit($userId, $level, $unit, $year);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work target (Level: %s) (Unit: %s) (Tahun: %s) showed", $level, $unit, $year),
            [
                'levels' => $response->levels,
                'indicators' => $response->indicators,
            ],
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        //
    }
}
