<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StopPickup;

class Child extends Model
{
    use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'children';

    protected $fillable = [
        'user_id',
        'child_name',
        'parent_id',
        'school_id',
        'pickup_name',
        'stop_name',
        'route_id',
        'secret_pin',
        'gender',
        'date_of_birth',
        'image',
        'child_adhaar_card_image',
        'class',
        'section',
        'home_address',
        'school_address',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    // School
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    // Route
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function pickupPoint()
    {
        return $this->belongsTo(StopPickup::class, 'pickup_name');
    }

    public function stopPoint()
    {
        return $this->belongsTo(StopPickup::class, 'stop_name');
    }

    public function getPickupLabelAttribute(): string
    {
        $pickupPoint = $this->relationLoaded('pickupPoint')
            ? $this->pickupPoint
            : $this->pickupPoint()->first(['pickup_name', 'stop_name']);

        return trim((string) ($pickupPoint->pickup_name ?? $pickupPoint->stop_name ?? ''));
    }

    public function getStopLabelAttribute(): string
    {
        $stopPoint = $this->relationLoaded('stopPoint')
            ? $this->stopPoint
            : $this->stopPoint()->first(['pickup_name', 'stop_name']);

        return trim((string) ($stopPoint->stop_name ?? $stopPoint->pickup_name ?? ''));
    }

    public static function getChildData(
        $searchValue,
        $columnName,
        $columnSortOrder,
        $draw,
        $row,
        $rowperpage
    ) {
        // Secure sort order
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        // Allowed sortable columns
        $allowedColumns = [
            'id',
            'child_name',
            'parent_id',
            'school_id',
            'pickup_name',
            'stop_name',
            'route_id',
            'gender',
            'date_of_birth',
            'class',
            'section',
            'home_address',
            'school_address',
            'status',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : 'id';

        // Base query (exclude deleted)
        $query = self::where(function ($q) {
            $q->where('deleted', 0)
              ->orWhereNull('deleted');
        });

        // Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('parent_name', 'like', "%$searchValue%")
                 ->orWhere('school_name', 'like', "%$searchValue%")
                 ->orWhere('name', 'like', "%$searchValue%")
                  ->orWhere('child_name', 'like', "%$searchValue%")
                  ->orWhere('class', 'like', "%$searchValue%")
                  ->orWhere('section', 'like', "%$searchValue%")
                  ->orWhere('home_address', 'like', "%$searchValue%")
                  ->orWhere('school_address', 'like', "%$searchValue%");
            });
        }

        // Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

     public static function getChildDataTotal($searchValue)
    {
        $query = self::where(function ($q) {
            $q->where('deleted', 0)
              ->orWhereNull('deleted');
        });

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
               $q->where('parent_name', 'like', "%$searchValue%")
                 ->orWhere('school_name', 'like', "%$searchValue%")
                 ->orWhere('name', 'like', "%$searchValue%")
                  ->orWhere('child_name', 'like', "%$searchValue%")
                  ->orWhere('class', 'like', "%$searchValue%")
                  ->orWhere('section', 'like', "%$searchValue%")
                  ->orWhere('home_address', 'like', "%$searchValue%")
                  ->orWhere('school_address', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
