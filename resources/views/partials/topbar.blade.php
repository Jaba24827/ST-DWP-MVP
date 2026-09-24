<header class="topbar">
  <button class="btn btn--ghost btn--sm" id="toggleNav" aria-label="Toggle navigation" aria-expanded="true" style="padding:7px">
    <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.7"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
  </button>
  <div style="flex:1;font-size:13px;color:var(--text-2);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
    SL-DWP <span style="color:var(--text-3)">/</span> <b style="color:var(--text)">@yield('crumb', 'Dashboard')</b>
  </div>
  <div class="topbar__search">
    <label class="sr-only" for="q">Search</label>
    <input id="q" type="search" placeholder="Search" style="padding:8px 11px;border:1px solid var(--line);border-radius:6px;background:var(--paper);color:var(--text);min-width:220px">
  </div>
  <button class="btn btn--ghost btn--sm" id="themeBtn" aria-label="Switch to dark mode" style="padding:7px">
    <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.7"><path d="M12 3v2M12 19v2M5 12H3M21 12h-2M6 6l1.5 1.5M16.5 16.5 18 18M18 6l-1.5 1.5M7.5 16.5 6 18"/><circle cx="12" cy="12" r="3.5"/></svg>
  </button>
  <div class="topbar__who" style="display:flex;align-items:center;gap:9px;padding-left:12px;border-left:1px solid var(--line)">
    <div style="width:33px;height:33px;border-radius:50%;background:var(--brand-soft);color:var(--brand);display:grid;place-items:center;font-size:12.5px;font-weight:600">
      {{ collect(explode(' ', auth()->user()->name))->map(fn($w)=>$w[0])->take(2)->implode('') }}
    </div>
    <div style="line-height:1.25;font-size:13px">
      {{ auth()->user()->name }}<br><span style="color:var(--text-2);font-size:11.5px">{{ auth()->user()->role?->name }}</span>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn--ghost btn--sm">Sign out</button>
    </form>
  </div>
</header>
