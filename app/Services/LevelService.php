<?php

namespace App\Services;

use App\Domains\Level;
use App\Domains\Unit;
use App\DTO\ConstructRequest;
use App\DTO\LevelCreateOrEditResponse;
use App\DTO\LevelInsertOrUpdateRequest;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LevelService
{

    private ?LevelRepository $levelRepository;
    private ?UserRepository $userRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
        $this->userRepository = $constructRequest->userRepository;
        $this->unitRepository = $constructRequest->unitRepository;
    }

    //use repo LevelRepository
    public function index()
    {
        return $this->levelRepository->find__all__with__parent();
    }

    //use repo LevelRepository
    public function create(): LevelCreateOrEditResponse
    {
        $response = new LevelCreateOrEditResponse();

        $response->levels = $this->levelRepository->find__all();

        return $response;
    }

    //use repo LevelRepository
    public function store(LevelInsertOrUpdateRequest $level): void
    {
        DB::transaction(function () use ($level) {
            $levelDomain = new Level();

            $levelDomain->name = strtoupper($level->name);
            $levelDomain->slug = Str::slug(strtolower($level->name));
            $levelDomain->parent_id = $this->levelRepository->find__id__by__slug($level->parent_level);

            $this->levelRepository->save($levelDomain);
        });
    }

    //use repo LevelRepository
    public function edit(string|int $id): LevelCreateOrEditResponse
    {
        $response = new LevelCreateOrEditResponse();

        $response->levels = $this->levelRepository->find__all();
        $response->level = $this->levelRepository->find__by__id($id);

        return $response;
    }

    //use repo LevelRepository, UnitRepository
    public function update(LevelInsertOrUpdateRequest $levelRequest): void
    {
        DB::transaction(function () use ($levelRequest) {
            $levelDomain = new Level();
            $unitDomain = new Unit();

            $level = $this->levelRepository->find__by__id($levelRequest->id);

            $levelDomain->name = strtoupper($levelRequest->name);
            $levelDomain->slug = Str::slug(strtolower($levelRequest->name));
            $levelDomain->parent_id = $this->levelRepository->find__id__by__slug($levelRequest->parent_level);

            //nama diubah
            if (strtoupper($level->name) !== strtoupper($levelRequest->name)) {
                $units = $this->unitRepository->find__all__by__levelId($levelRequest->id);
                foreach ($units as $unit) {
                    $this->unitRepository->
                }
            }

            $this->levelRepository->update__by__id($levelDomain, $levelRequest->id);
        });
    }

    //use repo LevelRepository, UserRepository
    public function levelsOfUser(string|int $id, bool $withSuperMaster)
    {
        $user = $this->userRepository->find__with__role_unit_level__by__id($id);

        $levels = null;
        if ($user->role->name === 'super-admin') {
            $levels = $withSuperMaster ? $this->levelRepository->find__all__with__childs__by__root() : $this->levelRepository->find__all__with__childs__by__parentId($this->levelRepository->find__id__by__slug('super-master'));
        } else {
            $levels = $this->levelRepository->find__all__with__childs__by__id($user->unit->level->id);
        }

        return $levels;
    }
}
