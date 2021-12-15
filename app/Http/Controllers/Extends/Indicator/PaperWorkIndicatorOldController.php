<?php

namespace App\Http\Controllers\Extends\Indicator;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Indicator;
use App\Models\IndicatorOnlyId;
use App\Models\User;
use App\Models\Unit;
use App\Models\Level;
use App\Models\LevelOnlySlug;
use App\Models\Realization;
use App\Models\Target;
use App\Rules\LevelMatchOnUserRole;
use App\Rules\UnitMatchOnUserRole;
use App\Rules\UnitMatchOnRequestLevel;

class PaperWorkIndicatorOldController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new LevelMatchOnUserRole($user)],
            'unit' => ['required_unless:level,super-master', 'string', new UnitMatchOnUserRole($user), new UnitMatchOnRequestLevel($request->query('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_unless' => ':attribute tidak boleh kosong.',
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
        $isSuperAdminOrAdmin = $isSuperAdmin || $user->role->name === 'admin';

        // 'permissions paper work indicator (create, edit, delete)' handler
        $childLevels = $isSuperAdmin ?
            Arr::flatten(
                LevelOnlySlug::with('childsRecursive')
                    ->whereNull('parent_id')
                    ->get()
                    ->toArray()
            ) :
            Arr::flatten(
                LevelOnlySlug::with('childsRecursive')
                    ->where(['id' => $user->unit->level->id])
                    ->get()
                    ->toArray()
            );

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work indicator 'level: %s' 'unit: %s' 'year: %s' showed", $request->query('level'), $request->query('unit'), $request->query('tahun')),
            [
                'levels' => $isSuperAdmin ?
                    Level::with('childsRecursive')->whereNull('parent_id')->get() :
                    Level::with('childsRecursive')->where(['id' => $user->unit->level->id])->get(),
                'indicators' => Indicator::with('childsHorizontalRecursive')
                    ->referenced()
                    ->rootHorizontal()
                    ->where(
                        $request->query('level') === 'super-master' ?
                            ['label' => 'super-master'] :
                            [
                                'level_id' => Level::firstWhere(['slug' => $request->query('level')])->id,
                                'label' => $request->query('unit') === 'master' ? 'master' : 'child',
                                'unit_id' => $request->query('unit') === 'master' ? null : Unit::firstWhere(['slug' => $request->query('unit')])->id,
                                'year' => $request->query('tahun')
                            ]
                    )
                    ->get(),
                'permissions' => [
                    'indicator' => [
                        'create' => $isSuperAdmin ? true : false,
                        'edit' => $isSuperAdmin ? true : false,
                        'delete' => $isSuperAdmin ? true : false,
                        'changes_order' => $isSuperAdminOrAdmin ? true : false
                    ],
                    'reference' => [
                        'create' => $isSuperAdmin ? true : false,
                        'edit' => (count($childLevels) > 1 && $isSuperAdminOrAdmin) ? true : false,
                    ],
                    'paper_work' => ['indicator' => [
                        'create' => (count($childLevels) > 1 && $isSuperAdminOrAdmin) ? true : false,
                        'edit' => (count($childLevels) > 1 && $isSuperAdminOrAdmin) ? true : false,
                        'delete' => (count($childLevels) > 1 && $isSuperAdminOrAdmin) ? true : false,
                    ]],
                ]
            ],
            null,
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail($request->header('X-User-Id'));

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Paper work indicators showed",
            [
                'indicators' => Indicator::with('childsHorizontalRecursive')
                    ->referenced()
                    ->rootHorizontal()
                    ->where(['label' => 'super-master'])
                    ->get(),
                'levels' => $user->role->name === 'super-admin' ?
                    Level::with('childsRecursive')
                        ->whereIn('parent_id', Arr::flatten(Level::whereNull('parent_id')->get(['id'])->toArray()))
                        ->get() :
                    Level::with('childsRecursive')
                        ->whereIn('parent_id', Arr::flatten(Level::where(['id' => $user->unit->level->id])->get(['id'])->toArray()))
                        ->get(),
            ],
            null,
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail($request->header('X-User-Id'));

        $attributes = [
            'indicators' => ['required'],
            'level' => ['required', 'string', new LevelMatchOnUserRole($user)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $levelId = Level::firstWhere(['slug' => $request->post('level')])->id;

        $sumOfIndicator = Indicator::where(['level_id' => $levelId, 'year' => $request->post('year')])->count();

        //memastikan paper work yang dibuat tidak duplikat pada level yang sama
        $validator->after(function ($validator) use ($sumOfIndicator, $request) {
            if ($sumOfIndicator > 0) {
                $validator->errors()->add(
                    'level', sprintf("Kertas kerja 'level: %s' 'year: %s' sudah tersedia.", $request->post('level'), $request->post('year'))
                );
            }
        });

        $indicatorsIdOfSuperMasterLevel = Arr::flatten(Indicator::referenced()->where(['label' => 'super-master'])->get(['id'])->toArray());

        //memastikan semua ID indikator dari request ada pada daftar ID indikator kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicatorsIdOfSuperMasterLevel) {
            foreach ($request->post('indicators') as $key => $value) {
                if (!in_array($value, $indicatorsIdOfSuperMasterLevel)) {
                    $validator->errors()->add(
                        'indicators', "'indicator ID: $value' tidak cocok dengan kertas kerja 'level: super-master'."
                    );
                }
            }
        });

        if ($validator->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        //cek indikator yang dipilih punya urutan sampai ke ROOT
        $childsOfSelectedIndicator = [];
        foreach ($request->post('indicators') as $key => $value) {
            $childsOfSelectedIndicator = array_merge($childsOfSelectedIndicator, Arr::flatten(
                IndicatorOnlyId::with('parentHorizontalRecursive')
                ->where(['id' => $value])
                ->get()
                ->toArray()
            ));
        }

        $indicators = Indicator::whereIn('id', array_unique($childsOfSelectedIndicator))->get();

        //section: paper work 'MASTER' creating

        //build ID
        $idListMaster = [];
        foreach ($indicators as $indicator) {
            $idListMaster[$indicator->id] = (string) Str::orderedUuid();
        }

        $i = 0;
        foreach ($indicators as $indicator) {
            $insert = DB::table('indicators')->insert([
                'id' => $idListMaster[$indicator->id],
                'indicator' => $indicator->indicator,
                'formula' => $indicator->formula,
                'measure' => $indicator->measure,
                'weight' => $indicator->getRawOriginal('weight'),
                'polarity' => $indicator->getRawOriginal('polarity'),
                'year' => $request->post('year'),
                'reducing_factor' => $indicator->getRawOriginal('reducing_factor'),
                'validity' => $indicator->getRawOriginal('validity'),
                'reviewed' => $indicator->reviewed,
                'referenced' => $indicator->referenced,
                'dummy' => $indicator->dummy,
                'label' => 'master',
                'unit_id' => null,
                'level_id' => $levelId,
                'order' => $i,
                'code' => $indicator->code,
                'parent_vertical_id' => $indicator->id,
                'parent_horizontal_id' => is_null($indicator->parent_horizontal_id) ? null : $idListMaster[$indicator->parent_horizontal_id],
                'created_by' => $request->header('X-User-Id'),

                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]);

            if (!$insert) {break;}

            //target 'MASTER' creating
            if (!is_null($indicator->validity)) {
                foreach ($indicator->validity as $key => $value) {
                    Target::create([
                        'id' => (string) Str::orderedUuid(),
                        'indicator_id' => $idListMaster[$indicator->id],
                        'month' => $key,
                        'value' => 0,
                        'locked' => 1,
                        'default' => 1,
                    ]);
                }
            }
            $i++;
        }
        //end section: paper work 'MASTER' creating

        //section: paper work 'CHILD' creating
        $units = Unit::where(['level_id' => $levelId])->get();
        $indicators = Indicator::where(['level_id' => $levelId, 'label' => 'master', 'year' => $request->post('year')])->get();

        foreach ($units as $unit) {
            //build ID
            $idListChild = [];
            foreach ($indicators as $indicator) {
                $idListChild[$indicator->id] = (string) Str::orderedUuid();
            }

            $i = 0;
            foreach ($indicators as $indicator) {
                $insert = DB::table('indicators')->insert([
                    'id' => $idListChild[$indicator->id],
                    'indicator' => $indicator->indicator,
                    'formula' => $indicator->formula,
                    'measure' => $indicator->measure,
                    'weight' => $indicator->getRawOriginal('weight'),
                    'polarity' => $indicator->getRawOriginal('polarity'),
                    'year' => $request->post('year'),
                    'reducing_factor' => $indicator->getRawOriginal('reducing_factor'),
                    'validity' => $indicator->getRawOriginal('validity'),
                    'reviewed' => $indicator->reviewed,
                    'referenced' => $indicator->referenced,
                    'dummy' => $indicator->dummy,
                    'label' => 'child',
                    'unit_id' => $unit->id,
                    'level_id' => $levelId,
                    'order' => $i,
                    'code' => $indicator->code,
                    'parent_vertical_id' => $indicator->id,
                    'parent_horizontal_id' => is_null($indicator->parent_horizontal_id) ? null : $idListChild[$indicator->parent_horizontal_id],
                    'created_by' => $request->header('X-User-Id'),

                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                ]);

                if (!$insert) {break;}

                //target & realization 'CHILD' creating
                if (!is_null($indicator->validity)) {
                    foreach ($indicator->validity as $key => $value) {
                        Target::create([
                            'id' => (string) Str::orderedUuid(),
                            'indicator_id' => $idListChild[$indicator->id],
                            'month' => $key,
                            'value' => 0,
                            'locked' => 1,
                            'default' => 1,
                        ]);

                        Realization::create([
                            'id' => (string) Str::orderedUuid(),
                            'indicator_id' => $idListChild[$indicator->id],
                            'month' => $key,
                            'value' => 0,
                            'locked' => 1,
                            'default' => 1,
                        ]);
                    }
                }
                $i++;
            }
        }
        //end section: paper work 'CHILD' creating

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work indicator 'level: %s' 'year: %s' creating successfully.", $request->post('level'), $request->post('year')),
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        //
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
    public function destroy($level, $unit, $year)
    {
        $attributes = [
            'level' => ['required', 'string'],
            'unit' => ['required', 'string', new UnitMatchOnRequestLevel($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        $validator = Validator::make($input, $attributes, $messages);

        $validator->after(function ($validator) use ($level) {
            if ($level === 'super-master') {
                $validator->errors()->add(
                    'level', sprintf("Kertas Kerja 'level: %s' tidak bisa dihapus.", $level)
                );
            }
        });

        $indicators = $unit === 'master' ?
        Indicator::with(['targets', 'realizations'])
            ->where(
                [
                    'level_id' => Level::firstWhere(['slug' => $level])->id,
                    'year' => $year,
                ]
            )
            ->get():
        Indicator::with(['targets', 'realizations'])
            ->where(
                [
                    'level_id' => Level::firstWhere(['slug' => $level])->id,
                    'unit_id' => Unit::firstWhere(['slug' => $unit])->id,
                    'year' => $year,
                ]
            )
            ->get();

        //cek apakah target or realization sudah ada yang di-edit
        $is_default = true;
        foreach ($indicators as $indicator) {
            foreach ($indicator->targets as $target) {
                if (!$target->default) {
                    $is_default = false;
                    break;
                }
            }

            if ($is_default === false) {break;}

            foreach ($indicator->realizations as $realization) {
                if (!$realization->default) {
                    $is_default = false;
                    break;
                }
            }
        }

        if (!$is_default) {
            $validator->after(function ($validator) use ($level, $unit, $year) {
                $validator->errors()->add(
                    'level', sprintf("Kertas kerja 'level: %s' 'unit: %s' 'year: %s' tidak bisa dihapus, karena sudah ada kertas kerja target atau realisasi.", $level, $unit, $year)
                );
            });
        }

        if ($validator->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        //deleting target & realization
        foreach ($indicators as $indicator) {
            foreach ($indicator->targets as $target) {
                Target::where(['id' => $target->id])->forceDelete();
            }

            foreach ($indicator->realizations as $realization) {
                Realization::where(['id' => $realization->id])->forceDelete();
            }
        }

        //deleting indicator
        $unit === 'master' ?
        Indicator::where(
                [
                    'level_id' => Level::firstWhere(['slug' => $level])->id,
                    'year' => $year,
                ]
            )
            ->forceDelete():
        Indicator::where(
                [
                    'level_id' => Level::firstWhere(['slug' => $level])->id,
                    'unit_id' => Unit::firstWhere(['slug' => $unit])->id,
                    'year' => $year,
                ]
            )
            ->forceDelete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work indicator 'level: %s' 'unit: %s' 'year: %s' deleting successfully.", $level, $unit, $year),
            null,
            null,
        );
    }
}
