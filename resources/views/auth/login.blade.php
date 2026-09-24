@extends('layouts.guest')
@section('title', 'Sign in')
@section('lede', 'One place to reach the people, notices and files your site depends on.')
@section('content')
  <h2>Sign in</h2>
  <p class="step-note">Step 1 of 2 — your work address and password.</p>
  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="field">
      <label for="email">Work email</label>
      <input id="email" name="email" type="email" autocomplete="username" required
             value="{{ old('email') }}" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
      @error('email') <p class="error-text" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn--wide" type="submit" style="margin-top:18px">Continue</button>
  </form>
@endsection
