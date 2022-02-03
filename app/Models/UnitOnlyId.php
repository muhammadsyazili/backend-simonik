<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UnitOnlyId extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'units';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'name',
        'slug',
        'parent_id',
        'level_id',

        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'parent_id',
        'level_id',
    ];

    /**
     * The "booting" function of model
     *
     * @return void
     */
    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    /**
    * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }

    public function childs()
    {
        return $this->hasMany(UnitOnlyId::class, 'parent_id', 'id');
    }
    // Childs recursive, loads all children
    public function childsRecursive()
    {
        return $this->childs()->with(['childsRecursive']);
    }

    public function parent()
    {
        return $this->belongsTo(UnitOnlyId::class, 'parent_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function indicators()
    {
        return $this->hasMany(Indicator::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
