@extends('layouts.app')
@section('title', 'Account security')
@section('crumb', 'Security')
@section('content')
  <div class="page__head"><h1>Your account security</h1><p>Password policy and multi-factor settings for your account.</p></div>
  <div class="grid grid--split">
    <div class="card">
      <h3>Change your password</h3>
      <form method="POST" action="{{ route('account.password.update') }}">
        @csrf @method('PUT')
        <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
        <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" required></div>
        <div class="field"><label for="password_confirmation">Repeat the new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
        <div class="policy">
          <div class="meter"><i id="pwMeter"></i></div>
          <ul id="pwRules">
            <li data-rule="len">At least 12 characters</li>
            <li data-rule="upper">One capital letter</li>
            <li data-rule="lower">One small letter</li>
            <li data-rule="digit">One number</li>
            <li data-rule="symbol">One symbol, such as ! ? # or @</li>
            <li data-rule="match">Both new entries match</li>
          </ul>
        </div>
        @error('password') <p class="error-text" role="alert">{{ $message }}</p> @enderror
        <button id="pwSubmit" class="btn btn--wide" type="submit" disabled style="margin-top:16px">Change password</button>
      </form>
    </div>
    <div class="card">
      <h3>Recent sign-in activity</h3>
      @forelse ($recent as $r)
        <div style="padding:9px 0;border-bottom:1px solid var(--line)">
          <div style="display:flex;justify-content:space-between"><span class="mono">{{ $r->action }}</span>
            <span class="tag {{ $r->result==='denied'?'tag--deny':'tag--ok' }}">{{ $r->result }}</span></div>
          <div style="font-size:12px;color:var(--text-3)">{{ $r->created_at->format('d/m H:i') }}</div>
        </div>
      @empty
        <div class="empty"><b>Nothing recorded yet</b></div>
      @endforelse
    </div>
  </div>
@endsection
