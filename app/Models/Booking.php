<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'bookings';

    protected $fillable = [
        'user_id',
        'school_id',
        'route_id',
        'package_type',
        'booking_type',
        'latitude',
        'longitude',
        'payment_status',
        'payment_mode',
        'contact_number',
        'status',
        'deleted',
    ];

    protected $attributes = [
        'status'  => 0,
        'deleted' => 0,
    ];

    public static function getBookingData($searchValue, $columnName, $columnSortOrder, $draw, $row, $rowperpage
    ) {
        // Secure sort order
        $columnSortOrder = in_array($columnSortOrder, ['asc', 'desc'])
            ? $columnSortOrder
            : 'asc';

        // Allowed columns
        $allowedColumns = [
            '_id',
            'user_id',
            'school_id',
            'route_id',
            'package_type',
            'booking_type',
            'latitude',
            'longitude',
            'payment_status',
            'payment_mode',
            'contact_number',
            'status',
            'deleted',
            'created_at',
            'updated_at',
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        //  Base query (exclude deleted)
       $query = self::where('deleted', 0);

        //  Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_type', 'like', "%$searchValue%")
                    ->orWhere('booking_type', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%")
                    ->orWhere('payment_status', 'like', "%$searchValue%")
                    ->orWhere('payment_mode', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }

        //  Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getBookingDataTotal($searchValue)
    {
         $query = self::where('deleted', 0);

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_type', 'like', "%$searchValue%")
                    ->orWhere('booking_type', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%")
                    ->orWhere('payment_status', 'like', "%$searchValue%")
                    ->orWhere('payment_mode', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }

        return $query->count();
    }
}
