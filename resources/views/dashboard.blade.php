@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb', 'Dashboard')
@section('content')
  <div class="page__head">
    <h1>Dashboard</h1>
    <p>Signed in as {{ auth()->user()->name }}, {{ strtolower(auth()->user()->role?->name) }} for {{ auth()->user()->sector?->name }}. This view is assembled from the permissions your role holds.</p>
  </div>

  <div class="grid grid--kpi">
    <div class="kpi"><span>Active accounts</span><b>{{ $activeUsers }}</b></div>
    <div class="kpi"><span>Documents you can open</span><b>{{ $visibleDocs }}</b><em>of {{ $heldDocs }} held</em></div>
    <div class="kpi {{ $denials7d > 0 ? 'kpi--alert' : '' }}"><span>Denied requests, 7 days</span><b>{{ $denials7d }}</b></div>
    <div class="kpi"><span>Notices for you</span><b>{{ $notices->count() }}</b></div>
  </div>

  <div class="grid grid--split dashboard-panels">
    <div class="card">
      <h3>Repository holdings by sector</h3>
      <canvas id="sectorChart" height="210" aria-label="Documents by sector and classification" role="img" data-holdings="{{ json_encode($holdings) }}"></canvas>
      <p class="dashboard-chart-note">Documents held by each sector, split by classification.</p>
    </div>
    <div class="card">
      <h3>Latest notices</h3>
      @forelse ($notices as $n)
        <div class="notice-item">
          <b>{{ $n->title }}</b>
          <p class="notice-item__body">{{ Str::limit($n->body, 120) }}</p>
          <div class="notice-item__meta">{{ $n->author->name }} · {{ $n->published_at->format('j M Y') }}</div>
        </div>
      @empty
        <div class="empty"><b>No notices yet</b>Notices published by managers appear here.</div>
      @endforelse
    </div>
  </div>
@endsection

@push('scripts')
@vite(['resources/js/dashboard-chart.js'])
@endpush
