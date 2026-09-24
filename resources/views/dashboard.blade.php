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

  <div class="grid grid--split" style="margin-top:14px">
    <div class="card">
      <h3>Repository holdings by sector</h3>
      <canvas id="sectorChart" height="210" aria-label="Documents by sector and classification" role="img"></canvas>
      <p style="font-size:12.5px;color:var(--text-3);margin-top:10px">Documents held by each sector, split by classification.</p>
    </div>
    <div class="card">
      <h3>Latest notices</h3>
      @forelse ($notices as $n)
        <div style="padding:12px 0;border-bottom:1px solid var(--line)">
          <b>{{ $n->title }}</b>
          <p style="font-size:13.5px;color:var(--text-2);margin:4px 0 0">{{ Str::limit($n->body, 120) }}</p>
          <div style="font-size:12px;color:var(--text-3);margin-top:6px">{{ $n->author->name }} · {{ $n->published_at->format('j M Y') }}</div>
        </div>
      @empty
        <div class="empty"><b>No notices yet</b>Notices published by managers appear here.</div>
      @endforelse
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
  var holdings = @json($holdings);
  var sectors = [...new Set(holdings.map(h => h.sector))];
  function series(cls){ return sectors.map(s => (holdings.find(h => h.sector===s && h.classification===cls)||{}).total || 0); }
  var style = getComputedStyle(document.documentElement);
  new Chart(document.getElementById('sectorChart'), {
    type: 'bar',
    data: { labels: sectors, datasets: [
      { label: 'Public', data: series('public'), backgroundColor: '#8FC7AC' },
      { label: 'Internal', data: series('internal'), backgroundColor: '#14794A' },
      { label: 'Restricted', data: series('restricted'), backgroundColor: '#A8403A' }
    ]},
    options: { responsive:true, scales:{ x:{stacked:true, grid:{display:false}}, y:{stacked:true, beginAtZero:true, ticks:{precision:0}} },
      plugins:{ legend:{ position:'bottom' } } }
  });
</script>
@endpush
