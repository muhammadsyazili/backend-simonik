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
use Illuminate\Database\Eloquent\Collection;

class LevelService
{
    private ?LevelRepository $levelRepository;
    private ?UserRepository $userRepository;
    private ?UnitRepository $unitRepository;

    private mixed $levels = null;
    private int $iter = 0;

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

        $levels = $this->levelRepository->find__all__with__parent();

        $newLevels = $levels->map(function ($item) {
            return [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
                'parent_name' => is_null($item->parent) ? '-' : $item->parent->name,
                'edit_modificable' => is_null($item->parent) ? false : true,
                'delete_modificable' => is_null($item->parent) ? false : true,
            ];
        });

        $response->levels = $newLevels;

        return $response;
    }

    //use repo LevelRepository
    public function create(): LevelCreateResponse
    {
        $response = new LevelCreateResponse();

        $levels = $this->levelRepository->find__all();

        $newLevels = $levels->map(function ($item) {
            return [
                'slug' => $item->slug,
                'name' => $item->name,
            ];
        });

        $response->levels = $newLevels;

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
            $levelDomain->parent_id = $this->levelRepository->find__id__by__slug($levelRequest->parentLevel);

            $this->levelRepository->save($levelDomain);
        });
    }

    //use repo LevelRepository
    public function edit(LevelEditRequest $levelRequest): LevelEditResponse
    {
        $response = new LevelEditResponse();

        $level = $this->levelRepository->find__by__id($levelRequest->id);

        $newLevel = [
            'id' => $level->id,
            'name' => $level->name,
            'parent_id' => $level->parent_id,
        ];

        $response->level = $newLevel;

        $levels = $this->levelRepository->find__all();

        $newLevels = $levels->map(function ($item) use ($level) {
            return [
                'id' => $item->id,
                'slug' => $item->slug,
                'name' => $item->name,
            ];
        });

        $response->levels = $newLevels;

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
            $levelDomain->parent_id = $this->levelRepository->find__id__by__slug($levelRequest->parentLevel);

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
    public function get_levels_by_userId(string|int $id, bool $withSuperMaster)
    {
        $user = $this->userRepository->find__with__role_unit_level__by__id($id);

        $levels = null;
        if ($user->role->name === 'super-admin') {
            $levels = $withSuperMaster ? $this->levelRepository->find__all__with__childs__by__root() : $this->levelRepository->find__all__with__childs__by__parentId($this->levelRepository->find__id__by__slug('super-master'));
        } else {
            $levels = $this->levelRepository->find__all__with__childs__by__id($user->unit->level->id);
        }

        $this->iter = 0; //reset iterator
        $this->mapping__levels_by_userId($levels);

        return $this->levels;
    }

    private function mapping__levels_by_userId(Collection $levels, bool $first = true): void
    {
        $levels->each(function ($item) use ($first) {
            $iteration = $first && $this->iter === 0 ? 0 : $this->iter;

            $this->levels[$iteration]['id'] = $item->id;
            $this->levels[$iteration]['slug'] = $item->slug;
            $this->levels[$iteration]['name'] = $item->name;

            $this->iter++;

            if (!empty($item->childsRecursive)) {
                $this->mapping__levels_by_userId($item->childsRecursive, false);
            }
        });
    }

    //use repo LevelRepository
    public function public_levels()
    {
        $levels = $this->levelRepository->find__all__not__superMaster();

        $newLevels = $levels->map(function ($item) {
            return [
                'name' => $item->name,
                'slug' => $item->slug,
            ];
        });

        return $newLevels;
    }

    //use repo LevelRepository, UnitRepository
    public function get_parents_by_levelSlug(string $slug)
    {
        $level = $this->levelRepository->find__with__parent__by__slug($slug);

        $units = $this->unitRepository->find__all__by__levelId($level->parent->id);

        $newUnits = $units->map(function ($item) {
            return [
                'name' => $item->name,
                'slug' => $item->slug,
            ];
        });

        return $newUnits;
    }

    //use repo LevelRepository
    public function get_categories()
    {
        $categories = $this->levelRepository->find__all__categories__not__superMaster();

        $temp = [];
        $i = 0;
        foreach ($categories as $category) {
            $levels = $this->levelRepository->find__all__by__parentId($category->parent_id);

            $levelCategoriesName = '';
            for ($j = 0; $j < count($levels); $j++) {
                $name = count($levels) - 1 === $j ? $levels[$j]->name : $levels[$j]->name . ', ';
                $levelCategoriesName .= $name;
            }

            $order = $i + 1;
            $temp[$i] = [
                'id' => $category->parent_id,
                'name' => "Kategori $order - $levelCategoriesName",
            ];

            $i++;
        }

        return $temp;
    }
}
