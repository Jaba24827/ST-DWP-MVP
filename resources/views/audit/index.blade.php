@extends('layouts.app')
@section('title', 'Audit log')
@section('crumb', 'Audit log')
@section('content')
  <div class="page__head">
    <h1>Audit log</h1>
    <p>Authentication attempts, multi-factor challenges, denied requests and changes to users, roles, notices and documents.</p>
  </div>
  <form method="GET" class="audit-filters">
    <label class="sr-only" for="audit_kind">Filter by event type</label>
    <select id="audit_kind" name="kind" aria-label="Filter by event type">
      <option value="">All events</option>
      <option value="auth" {{ request('kind')==='auth'?'selected':'' }}>Authentication</option>
      <option value="denied" {{ request('kind')==='denied'?'selected':'' }}>Denied access</option>
      <option value="change" {{ request('kind')==='change'?'selected':'' }}>Changes</option>
    </select>
    <label class="sr-only" for="audit_q">Search audit events</label>
    <input id="audit_q" type="search" name="q" aria-label="Search audit events" value="{{ request('q') }}" placeholder="Search actor or action">
    <button class="btn btn--ghost btn--sm">Filter</button>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th><th>Result</th></tr></thead>
      <tbody>
      @forelse ($logs as $l)
        <tr>
          <td class="mono">{{ $l->created_at->format('d/m H:i') }}</td>
          <td class="mono">{{ $l->actor }}</td>
          <td class="mono">{{ $l->action }}</td>
          <td>{{ $l->target }}</td>
          <td><span class="tag {{ $l->result==='denied'?'tag--deny':'tag--ok' }}">{{ $l->result }}</span></td>
        </tr>
      @empty
        <tr><td colspan="5" class="empty"><b>No matching events</b>Widen the filter to see more of the log.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  {{ $logs->links() }}
@endsection
