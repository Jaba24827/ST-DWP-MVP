@extends('layouts.app')
@section('title', 'Users')
@section('crumb', 'Users')
@section('content')
  <div class="page__head">
    <h1>Users</h1>
    <p>Accounts and role assignments. A role change takes effect the next time that user signs in.</p>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Sector</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      @foreach ($users as $u)
        <tr>
          <td><b>{{ $u->name }}</b><br><span class="mono">{{ $u->position }}</span></td>
          <td class="mono">{{ $u->email }}</td>
          <td>{{ $u->sector?->name }}</td>
          <td>
            <form method="POST" action="{{ route('users.update', $u) }}">
              @csrf @method('PATCH')
              <select name="role_id" onchange="this.form.submit()">
                @foreach ($roles as $r)<option value="{{ $r->id }}" {{ $u->role_id === $r->id ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach
              </select>
            </form>
          </td>
          <td><span class="tag {{ $u->is_active ? 'tag--ok' : 'tag--deny' }}">{{ $u->is_active ? 'active' : 'deactivated' }}</span></td>
          <td>
            <form method="POST" action="{{ route('users.update', $u) }}">
              @csrf @method('PATCH')
              <input type="hidden" name="is_active" value="{{ $u->is_active ? 0 : 1 }}">
              <button class="btn btn--ghost btn--sm">{{ $u->is_active ? 'Deactivate' : 'Restore' }}</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  {{ $users->links() }}
@endsection
