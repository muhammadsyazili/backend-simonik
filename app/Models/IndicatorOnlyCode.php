<?php

namespace App\Models;

use App\Casts\JsonToArrayCastsAttribute;
use App\Casts\PolarityCastsAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndicatorOnlyCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'indicators';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'indicator',
        'formula',
        'measure',
        'weight',
        'polarity',
        'year',
        'reducing_factor',
        'validity',
        'reviewed',
        'referenced',
        'dummy',
        'type',
        'fdx',

        'label',
        'unit_id',
        'level_id',
        'order',
        'parent_vertical_id',
        'parent_horizontal_id',
        'created_by',

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
        'indicator',
        'formula',
        'measure',
        'weight',
        'polarity',
        'year',
        'reducing_factor',
        'validity',
        'reviewed',
        'referenced',
        'dummy',
        'type',
        'fdx',

        'label',
        'unit_id',
        'level_id',
        'order',
        'code',
        'parent_vertical_id',
        'parent_horizontal_id',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'reducing_factor' => 'boolean',
        'reviewed' => 'boolean',
        'referenced' => 'boolean',
        'dummy' => 'boolean',
        'weight' => JsonToArrayCastsAttribute::class,
        'validity' => JsonToArrayCastsAttribute::class,
        'polarity' => PolarityCastsAttribute::class,
    ];

    /**
     * The "booting" function of model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            parent::boot();

            static::creating(function ($model) {
                if (empty($model->{$model->getKeyName()})) {
                    $model->{$model->getKeyName()} = Str::uuid()->toString();
                }
            });
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

    /**
     * Scope a query to only include referenced.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeReferenced($query)
    {
        $query->where(['referenced' => 1]);
    }

    /**
     * Scope a query to only exclude referenced.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeNotReferenced($query)
    {
        $query->where(['referenced' => 0]);
    }

    /**
     * Scope a query to only include root horizontal.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeRootHorizontal($query)
    {
        $query->whereNull('parent_horizontal_id');
    }

    public function realizations()
    {
        return $this->hasMany(Realization::class);
    }

    public function targets()
    {
        return $this->hasMany(Target::class);
    }

    public function childsVertical()
    {
        return $this->hasMany(IndicatorOnlyCode::class, 'parent_vertical_id', 'id');
    }
    // Vertical recursive, loads all children
    public function childsVerticalRecursive()
    {
        return $this->childsVertical()->with('childsVerticalRecursive');
    }

    public function childsHorizontal()
    {
        return $this->hasMany(IndicatorOnlyCode::class, 'parent_horizontal_id', 'id')->referenced();
    }
    // Horizontal recursive, loads all children
    public function childsHorizontalRecursive()
    {
        return $this->childsHorizontal()->with('childsHorizontalRecursive');
    }

    public function parentVertical()
    {
        return $this->belongsTo(IndicatorOnlyCode::class, 'parent_vertical_id', 'id');
    }

    public function parentHorizontal()
    {
        return $this->belongsTo(IndicatorOnlyCode::class, 'parent_horizontal_id', 'id');
    }
    // Horizontal recursive, loads all parent
    public function parentHorizontalRecursive()
    {
        return $this->parentHorizontal()->with('parentHorizontalRecursive');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
