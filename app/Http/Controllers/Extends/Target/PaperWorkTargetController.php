<?php

namespace App\Http\Controllers\Extends\Target;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Models\Indicator;
use App\Models\User;
use App\Models\Level;
use App\Models\Unit;
use App\Rules\ValidRequestLevelBaseOnUserRole;
use App\Rules\ValidRequestUnitBaseOnUserRole;
use App\Rules\ValidRequestUnitBaseOnRequestLevel;

class PaperWorkTargetController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail(request()->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new ValidRequestLevelBaseOnUserRole($user)],
            'unit' => ['required', 'string', new ValidRequestUnitBaseOnUserRole($user), new ValidRequestUnitBaseOnRequestLevel($request->query('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        $validator = Validator::make($input, $attributes);

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
            sprintf("Paper work target 'level: %s' 'unit: %s' 'year: %s' showed", $request->query('level'), $request->query('unit'), $request->query('tahun')),
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
