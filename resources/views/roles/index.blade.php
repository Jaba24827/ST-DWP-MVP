@extends('layouts.app')
@section('title', 'Roles and permissions')
@section('crumb', 'Roles')
@section('content')
  <div class="page__head">
    <h1>Roles and permissions</h1>
    <p>This grid is the authorisation source. Every screen and every action reads from it.</p>
  </div>
  <div class="table-wrap">
    <table class="matrix">
      <thead><tr><th>Permission</th>@foreach ($roles as $role)<th>{{ $role->name }}</th>@endforeach</tr></thead>
      <tbody>
      @foreach ($permissions as $perm)
        <tr>
          <td><span class="mono">{{ $perm->key }}</span><br><span style="font-size:12px;color:var(--text-3)">{{ $perm->description }}</span></td>
          @foreach ($roles as $role)
            <td style="text-align:center">
              <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf @method('PATCH')
                @foreach ($role->permissions as $p)
                  @if ($p->id !== $perm->id)<input type="hidden" name="permissions[]" value="{{ $p->id }}">@endif
                @endforeach
                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                       {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}
                       aria-label="{{ $role->name }} holds {{ $perm->key }}">
              </form>
            </td>
          @endforeach
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <p style="font-size:12.5px;color:var(--text-3);margin-top:10px">
    Least privilege: a role holds only the permissions its work requires. An administrator cannot remove
    <span class="mono">roles.manage</span> from their own role — that guard sits in the controller, not in this page.
  </p>
@endsection
