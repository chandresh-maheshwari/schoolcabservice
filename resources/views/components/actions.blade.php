@php
    $canEditUser = auth()->check() && auth()->user()->canAccessAdminRoute('users.edit');
    $canDeleteUser = auth()->check() && auth()->user()->canAccessAdminRoute('users.destroy');
@endphp

@if ($canEditUser)
    <a href="{{ route('users.edit', $row->id) }}" class="btn btn-warning">Edit</a>
@endif

@if ($canDeleteUser)
    <form action="{{ route('users.destroy', $row->id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
@endif
