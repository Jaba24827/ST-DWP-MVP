@extends('layouts.app')
@section('title', 'Staff directory')
@section('crumb', 'Directory')
@section('content')
  <div class="page__head"><h1>Staff directory</h1><p>Contact details for staff across the Foundation's sites.</p></div>
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name">
    <select name="sector_id" onchange="this.form.submit()">
      <option value="">All sectors</option>
      @foreach ($sectors as $s)<option value="{{ $s->id }}" {{ (int)request('sector_id')===$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach
    </select>
    <button class="btn btn--ghost btn--sm">Filter</button>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Position</th><th>Sector</th><th>Site</th><th>Email</th></tr></thead>
      <tbody>
      @forelse ($people as $p)
        <tr><td><b>{{ $p->name }}</b></td><td>{{ $p->position }}</td><td>{{ $p->sector?->name }}</td><td>{{ $p->site?->name }}</td><td class="mono">{{ $p->email }}</td></tr>
      @empty
        <tr><td colspan="5" class="empty"><b>No one matches that search</b></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  {{ $people->links() }}
@endsection
