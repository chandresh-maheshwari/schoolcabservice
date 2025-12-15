<a href="{{ route('users.edit', $row->id) }}" class="btn btn-warning">Edit</a>
<form action="{{ route('users.destroy', $row->id) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger">Delete</button>
</form>
