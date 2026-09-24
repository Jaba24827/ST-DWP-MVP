@extends('layouts.guest')
@section('title', 'Verify')
@section('lede', 'Confirm it is you.')
@section('content')
  <h2>Confirm it is you</h2>
  <p class="step-note">Step 2 of 2 — a six-digit code, valid for five minutes.</p>

  @if (! $sent)
    <p style="font-size:13.5px;color:var(--text-2)">Where should the code go?</p>
    <form method="POST" action="{{ route('mfa.send') }}">
      @csrf
      <div style="display:flex;gap:8px;margin:10px 0 16px">
        <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid var(--line);border-radius:6px;flex:1">
          <input type="radio" name="channel" value="email" {{ $channel === 'email' ? 'checked' : '' }}> Email <span class="mono">{{ $emailMask }}</span>
        </label>
        <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid var(--line);border-radius:6px;flex:1">
          <input type="radio" name="channel" value="sms" {{ $channel === 'sms' ? 'checked' : '' }}> SMS <span class="mono">{{ $smsMask }}</span>
        </label>
      </div>
      <button class="btn btn--wide" type="submit">Send the code</button>
    </form>
  @else
    <form method="POST" action="{{ route('mfa.verify') }}">
      @csrf
      <div class="field">
        <label for="code">Six-digit code</label>
        <input id="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
               style="letter-spacing:.4em;font-family:'IBM Plex Mono',monospace;font-size:19px;text-align:center" required>
        @error('code') <p class="error-text" role="alert">{{ $message }}</p> @enderror
      </div>
      <button id="verifySubmit" class="btn btn--wide" type="submit" style="margin-top:18px">Verify and sign in</button>
    </form>
    <form method="POST" action="{{ route('mfa.send') }}" style="margin-top:12px">
      @csrf<input type="hidden" name="channel" value="{{ $channel }}">
      <button class="btn btn--ghost btn--sm" type="submit">Send a new code</button>
    </form>
  @endif
@endsection
