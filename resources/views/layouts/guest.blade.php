<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Sign in') · SL-DWP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/sldwp.css'])

</head>
<body>
<section id="signin">
  <div class="signin-brand">
    <img src="{{ asset('assets/img/logo-onDark.svg') }}" alt="" width="40" height="40">
    <div class="signin-lede">
      <h1>@yield('lede', 'Sign in to the digital workplace.')</h1>
      <p>Every request is checked against your role, and MFA confirms it is really you.</p>
    </div>
    <div></div>
  </div>
  <div class="signin-panel">
    <div class="signin-card">@yield('content')</div>
  </div>
</section>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/sldwp.js') }}"></script>
</body>
</html>
