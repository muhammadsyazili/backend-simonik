<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Level;

class LevelController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function edit($id)
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
    public function destroy($id)
    {
        //
    }

    /**
     * Display a listing of levels by user the resource.
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function levelsOfUser(Request $request, $id)
    {
        $user = User::with(['role', 'unit.level'])->findOrFail($id);

        $levels = null;
        if ($user->role->name === 'super-admin') {
            if ($request->query('with-super-master')) {
                $levels = Level::with('childsRecursive')->whereNull('parent_id')->get();
            } else {
                $levels = Level::with('childsRecursive')->where(['parent_id' => Level::firstWhere(['slug' => 'super-master'])->id])->get();
            }
        } else {
            $levels = Level::with('childsRecursive')->where(['id' => $user->unit->level->id])->get();
        }


        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Levels of '$id'",
            $levels,
            null,
        );
    }
}
