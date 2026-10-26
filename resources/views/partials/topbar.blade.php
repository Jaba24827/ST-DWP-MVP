<header class="topbar">
  <button class="btn btn--ghost btn--sm topbar__icon-btn" id="toggleNav" aria-label="Toggle navigation" aria-expanded="true">
    <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.7"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
  </button>

  <div class="topbar__crumb">
    SL-DWP <span class="topbar__separator">/</span>
    <b class="topbar__crumb-title">@yield('crumb', 'Dashboard')</b>
  </div>

  <div class="topbar__search">
    <label class="sr-only" for="q">Search</label>
    <input id="q" type="search" placeholder="Search">
  </div>

  <button class="btn btn--ghost btn--sm topbar__icon-btn" id="themeBtn" aria-label="Switch to dark mode">
    <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="1.7"><path d="M12 3v2M12 19v2M5 12H3M21 12h-2M6 6l1.5 1.5M16.5 16.5 18 18M18 6l-1.5 1.5M7.5 16.5 6 18"/><circle cx="12" cy="12" r="3.5"/></svg>
  </button>

  <div class="topbar__who">
    <div class="topbar__avatar">
      {{ collect(explode(' ', auth()->user()->name))->map(fn($w)=>$w[0])->take(2)->implode('') }}
    </div>
    <div class="topbar__identity">
      {{ auth()->user()->name }}<br>
      <span class="topbar__role">{{ auth()->user()->role?->name }}</span>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="btn btn--ghost btn--sm">Sign out</button>
    </form>
  </div>
</header>