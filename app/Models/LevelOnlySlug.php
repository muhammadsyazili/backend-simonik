<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelOnlySlug extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'levels';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'name',
        'parent_id',

        'created_at',
        'updated_at',
        'deleted_at'
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
    ];

    public function childs()
    {
        return $this->hasMany(LevelOnlySlug::class, 'parent_id', 'id');
    }
    // Childs recursive, loads all children
    public function childsRecursive()
    {
        return $this->childs()->with(['childsRecursive']);
    }

    public function parent()
    {
        return $this->belongsTo(LevelOnlySlug::class, 'parent_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
