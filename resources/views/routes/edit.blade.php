@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');

        $routesActionUrl = $isSchoolPanel
            ? route('school.routes.update', ['schoolSlug' => $schoolSlug, 'route' => $route->id])
            : route('routes.update', $route->id);

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

        $formHeading = 'Edit Route Details';
        $formId = 'routeEditForm';
        $formMethod = 'PUT';
        $submitButtonId = 'updateRouteBtn';
        $submitButtonText = 'Update Route';
        $loadingText = 'Updating...';
        $successText = 'Route updated successfully';
        $routeRecord = $route;
    @endphp

    @include('routes.partials.form')
@endsection
