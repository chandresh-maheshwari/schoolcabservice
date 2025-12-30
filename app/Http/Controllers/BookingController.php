<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PackageDetail;
use App\Models\Route;
use App\Models\School;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display booking listing page.
     * created by ns
     */
    public function index()
    {
        return view('booking.index');
    }

    /**
     * Display booking create form.
     * created by ns
     */
    public function create()
    {
        $packages = PackageDetail::select('package_type', 'booking_type')
            ->where('deleted', 0)
            ->get();

        $schoolData = School::select('school_name')
            ->where('deleted', 0)
            ->get();

        $routeData = Route::select('name')
            ->where('deleted', 0)
            ->get();

        return view('booking.create', compact('packages', 'schoolData', 'routeData'));
    }

    /**
     * Store booking data.
     * created by ns
     */
    public function store(Request $request)
    {
        $request->validate([
            'package_type'   => 'required|string|max:255',
            'booking_type'   => 'required|string|max:255',
            'school_id'      => 'required',
            'route_id'       => 'required',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'payment_status' => 'required|string|max:255',
            'payment_mode'   => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
        ]);

        try {
            Booking::create([
                'school_id'      => $request->school_id,
                'route_id'       => $request->route_id,
                'package_type'   => $request->package_type,
                'booking_type'   => $request->booking_type,
                'latitude'       => $request->latitude,
                'longitude'      => $request->longitude,
                'payment_status' => $request->payment_status,
                'payment_mode'   => $request->payment_mode,
                'contact_number' => $request->contact_number,
                'status'         => 0,
                'deleted'        => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display booking edit form.
     * created by ns
     */
    public function edit($id)
    {
        $booking = Booking::findOrFail($id);

        $packages = PackageDetail::select('package_type', 'booking_type')
            ->where('deleted', 0)
            ->get();

        $schoolData = School::select('_id', 'school_name')
            ->where('deleted', 0)
            ->get();

        $routeData = Route::select('_id', 'name')
            ->where('deleted', 0)
            ->get();

        return view('booking.edit', compact(
            'booking',
            'packages',
            'schoolData',
            'routeData'
        ));
    }

    /**
     * Update booking data.
     * created by ns
     */
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'package_type'   => 'required|string|max:255',
            'booking_type'   => 'required|string|max:255',
            'school_id'      => 'required',
            'route_id'       => 'required',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'payment_status' => 'required|string|max:255',
            'payment_mode'   => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
        ]);

        $booking->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
        ]);
    }

    /**
     * Soft delete booking record.
     * created by ns
     */
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->deleted = 1;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted Successfully.',
        ]);
    }

    /**
     * Toggle booking active/inactive status.
     * created by ns
     */
    public function toggleStatus($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->status = $booking->status == 1 ? 0 : 1;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
        ]);
    }

    /**
     * Get active booking count.
     * created by ns
     */
    public function getActiveCount()
    {
        $activeCount = Booking::where('deleted', 0)
            ->where('status', true)
            ->count();

        return response()->json(['count' => $activeCount]);
    }

    /**
     * Fetch booking list for DataTable.
     * created by ns
     */
    public function bookingList(Request $request)
    {
        $draw        = $request->input('sEcho');
        $row         = (int) $request->input('iDisplayStart', 0);
        $rowperpage  = (int) $request->input('iDisplayLength', 10);
        $indexColumn = $request->input('iSortCol_0', 0);
        $columnName  = $request->input('mDataProp_' . $indexColumn, '_id');

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
        ];

        $columnName = in_array($columnName, $allowedColumns)
            ? $columnName
            : '_id';

        $columnSortOrder = in_array(
            $request->input('sSortDir_0'),
            ['asc', 'desc']
        ) ? $request->input('sSortDir_0') : 'asc';

        $searchValue = $request->input('sSearch');

        $bookingDetail = Booking::getBookingData(
            $searchValue,
            $columnName,
            $columnSortOrder,
            $draw,
            $row,
            $rowperpage
        );

        $totalRecords          = Booking::where('deleted', 0)->count();
        $totalRecordwithFilter = Booking::getBookingDataTotal($searchValue);

        $data = [];

        foreach ($bookingDetail as $booking) {
            $data[] = [
                'id'             => (string) $booking->_id,
                'user_id'        => $booking->user_id,
                'school_id'      => $booking->school_id,
                'route_id'       => $booking->route_id,
                'package_type'   => $booking->package_type,
                'booking_type'   => $booking->booking_type,
                'latitude'       => $booking->latitude,
                'longitude'      => $booking->longitude,
                'payment_status' => $booking->payment_status,
                'payment_mode'   => $booking->payment_mode,
                'contact_number' => $booking->contact_number,
                'description'    => $booking->description,
                'status'         => $booking->status,
            ];
        }

        return response()->json([
            "draw"            => intval($draw),
            "recordsTotal"    => $totalRecords,
            "recordsFiltered" => $totalRecordwithFilter,
            "data"            => $data,
        ]);
    }
}
