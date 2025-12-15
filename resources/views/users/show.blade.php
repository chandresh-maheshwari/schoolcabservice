@extends('front_layouts.index')

@section('content')
    @include('front_layouts.edit_user', ['user' => $user])
@endsection 