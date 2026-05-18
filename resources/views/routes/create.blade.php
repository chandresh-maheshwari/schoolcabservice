@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');

        $routesActionUrl = $isSchoolPanel
            ? route('school.routes.store', ['schoolSlug' => $schoolSlug])
            : route('routes.store');

        $routesIndexUrl = $isSchoolPanel
            ? route('school.routes.index', ['schoolSlug' => $schoolSlug])
            : route('routes.index');
        $routePreviewUrl = $isSchoolPanel
            ? route('school.routes.google-preview', ['schoolSlug' => $schoolSlug])
            : route('routes.google-preview');
        $customLocationSearchUrl = $isSchoolPanel
            ? route('school.routes.customLocations.search', ['schoolSlug' => $schoolSlug])
            : route('routes.customLocations.search');
        $customLocationStoreUrl = $isSchoolPanel
            ? route('school.routes.customLocations.store', ['schoolSlug' => $schoolSlug])
            : route('routes.customLocations.store');
        $vehicleDriverLookupUrl = $isSchoolPanel
            ? route('school.routes.vehicleDrivers', ['schoolSlug' => $schoolSlug, 'vehicle' => '__VEHICLE__'])
            : route('routes.vehicleDrivers', ['vehicle' => '__VEHICLE__']);

        $formHeading = 'Add Route Details';
        $formId = 'routeCreateForm';
        $formMethod = 'POST';
        $submitButtonId = 'submitRouteBtn';
        $submitButtonText = 'Create Route';
        $loadingText = 'Saving...';
        $successText = 'Route created successfully';
        $routeRecord = null;
        $sendToPhoneEmail = (string) (optional(auth()->user())->email ?? '');
    @endphp

    @include('routes.partials.form')
@endsection
