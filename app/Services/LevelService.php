<?php

namespace App\Services;

use App\Domains\Level;
use App\Domains\Unit;
use App\DTO\ConstructRequest;
use App\DTO\LevelEditResponse;
use App\DTO\LevelCreateResponse;
use App\DTO\LevelDestroyRequest;
use App\DTO\LevelEditRequest;
use App\DTO\LevelIndexResponse;
use App\DTO\LevelUpdateRequest;
use App\DTO\LevelStoreRequest;
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
    public function index(): LevelIndexResponse
    {
        $response = new LevelIndexResponse();

        $response->levels = $this->levelRepository->find__all__with__parent();

        return $response;
    }

    //use repo LevelRepository
    public function create(): LevelCreateResponse
    {
        $response = new LevelCreateResponse();

        $response->levels = $this->levelRepository->find__all();

        return $response;
    }

    //use repo LevelRepository
    public function store(LevelStoreRequest $levelRequest): void
    {
        DB::transaction(function () use ($levelRequest) {
            $levelDomain = new Level();

            $name__uppercase = strtoupper($levelRequest->name);
            $name__lowercase = strtolower($levelRequest->name);

            $levelDomain->name = $name__uppercase;
            $levelDomain->slug = Str::slug($name__lowercase);
            $levelDomain->parent_id = $this->levelRepository->find__id__by__slug($levelRequest->parent_level);

            $this->levelRepository->save($levelDomain);
        });
    }

    //use repo LevelRepository
    public function edit(LevelEditRequest $levelRequest): LevelEditResponse
    {
        $response = new LevelEditResponse();

        $response->levels = $this->levelRepository->find__all();
        $response->level = $this->levelRepository->find__by__id($levelRequest->id);

        return $response;
    }

    //use repo LevelRepository, UnitRepository
    public function update(LevelUpdateRequest $levelRequest): void
    {
        DB::transaction(function () use ($levelRequest) {
            $levelDomain = new Level();
            $unitDomain = new Unit();

            $level = $this->levelRepository->find__by__id($levelRequest->id);

            $name__uppercase = strtoupper($levelRequest->name);
            $name__lowercase = strtolower($levelRequest->name);

            $levelDomain->id = $levelRequest->id;
            $levelDomain->name = $name__uppercase;
            $levelDomain->slug = Str::slug($name__lowercase);
            $levelDomain->parent_id = $this->levelRepository->find__id__by__slug($levelRequest->parent_level);

            $this->levelRepository->update__by__id($levelDomain);

            //nama level diubah
            if (strtolower($level->name) !== $name__lowercase) {
                $units = $this->unitRepository->find__all__by__levelId($levelRequest->id);

                foreach ($units as $unit) {
                    $unitDomain->id = $unit->id;
                    $unitDomain->name = str_replace(strtoupper($level->name), $name__uppercase, $unit->name);
                    $unitDomain->slug = str_replace(strtolower($level->name), $name__lowercase, $unit->slug);

                    $this->unitRepository->update__name_slug__by__id($unitDomain);
                }
            }
        });
    }

    //use repo LevelRepository
    public function destroy(LevelDestroyRequest $levelRequest): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($levelRequest) {
            $this->levelRepository->delete__by__id($levelRequest->id);
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
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

    //use repo LevelRepository, UnitRepository
    public function levelsOfParents(string $slug)
    {
        $level = $this->levelRepository->find__with__parent__by__slug($slug);

        return $this->unitRepository->find__all__by__levelId($level->parent->id);
    }
}
