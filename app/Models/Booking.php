<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // protected $connection = 'mongodb';
    protected $table = 'bookings';

    protected $fillable = [
        'child_id',
        'user_id',
        'school_id',
        'route_id',
        'package_type_id',
        'booking_type_id',
        'short_description',
        'latitude',
        'longitude',
        'payment_status',
        'payment_mode',
        'contact_number',
        'status',
        'deleted',
    ];

    /** Package Type Relationship */
    public function child()
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    /** Package Type Relationship */
    public function packageType()
    {
        return $this->belongsTo(PackageDetail::class, 'package_type_id');
    }

    /** Booking Type Relationship */
    public function bookingType()
    {
        return $this->belongsTo(PackageDetail::class, 'booking_type_id');
    }

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
            'id',
            'user_id',
            'school_id',
            'route_id',
            'package_type_id',
            'booking_type_id',
            'short_description',
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
            : 'id';

        //  Base query (exclude deleted)
      $query = self::with(['packageType','bookingType'])
             ->where('deleted', 0);

        //  Search filter
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_type_id', 'like', "%$searchValue%")
                    ->orWhere('booking_type_id', 'like', "%$searchValue%")
                    ->orWhere('latitude', 'like', "%$searchValue%")
                    ->orWhere('longitude', 'like', "%$searchValue%")
                    ->orWhere('payment_status', 'like', "%$searchValue%")
                    ->orWhere('payment_mode', 'like', "%$searchValue%")
                    ->orWhere('contact_number', 'like', "%$searchValue%");
            });
        }

        // dd($query->get());
        //  Pagination + Sorting
        return $query
            ->orderBy($columnName, $columnSortOrder)
            ->skip((int) $row)
            ->take((int) $rowperpage)
            ->get();
    }

    public static function getBookingDataTotal($searchValue)
    {
        $query = self::with(['packageType','bookingType'])
             ->where('deleted', 0);


        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('package_type_id', 'like', "%$searchValue%")
                    ->orWhere('booking_type_id', 'like', "%$searchValue%")
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
