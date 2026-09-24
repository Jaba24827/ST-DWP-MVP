@php
  $items = [
    'Work' => [
      ['dashboard', 'dashboard.view', route('dashboard'), 'M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6v-9h-6v9Zm0-16v5h6V4h-6Z', 'Dashboard'],
      ['notices.index', 'announcements.view', route('notices.index'), 'M4 8h10l6-3v14l-6-3H4Z', 'Notices'],
      ['directory.index', 'directory.view', route('directory.index'), 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 8a8 8 0 0 1 16 0', 'Directory'],
      ['documents.index', 'documents.view', route('documents.index'), 'M6 3h8l4 4v14H6ZM14 3v4h4', 'Documents'],
    ],
    'Administration' => [
      ['users.index', 'users.manage', route('users.index'), 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-6 9a6 6 0 0 1 12 0M17 11h4M19 9v4', 'Users'],
      ['roles.index', 'roles.manage', route('roles.index'), 'M12 3l8 3v6c0 4-3.5 7.5-8 9-4.5-1.5-8-5-8-9V6Z', 'Roles'],
      ['audit.index', 'audit.view', route('audit.index'), 'M5 4h14v16H5ZM8 9h8M8 13h8M8 17h4', 'Audit log'],
    ],
    'Your account' => [
      ['account.password.edit', 'account.security', route('account.password.edit'), 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6ZM12 11v3M12 9h.01', 'Security'],
    ],
  ];
@endphp
<aside class="sidebar">
  <div class="sidebar__head">
    <img src="{{ asset('assets/img/logo.svg') }}" alt="" width="36" height="36" style="margin-right:11px">
    <div>
      <div style="font-weight:700;font-size:15px;color:var(--brand)">SL-DWP</div>
      <div style="font-size:11.5px;color:var(--text-2)">Digital Workplace</div>
    </div>
  </div>
  <nav class="sidebar__nav" aria-label="Main">
    @foreach ($items as $group => $rows)
      @php $rows = array_filter($rows, fn($r) => auth()->user()->can_($r[1])); @endphp
      @continue(empty($rows))
      <div class="sidebar__group">{{ $group }}</div>
      @foreach ($rows as [$name, $perm, $url, $icon, $label])
        <a class="nav-item {{ request()->routeIs($name) ? 'is-active' : '' }}" href="{{ $url }}">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}" stroke-linejoin="round" stroke-linecap="round"/></svg>
          <span>{{ $label }}</span>
        </a>
      @endforeach
    @endforeach
  </nav>
  <div class="sidebar__foot">SL-DWP · MySQL + Laravel</div>
</aside>
