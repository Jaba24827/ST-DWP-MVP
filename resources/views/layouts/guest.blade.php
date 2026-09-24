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
<style>
  #signin{min-height:100vh;display:grid;grid-template-columns:1.05fr .95fr;
    background:linear-gradient(158deg,#0E5A37 0%,#14794A 52%,#0B3F28 100%)}
  .signin-brand{padding:clamp(26px,5vw,60px);color:#D9EEE2;display:flex;flex-direction:column;justify-content:space-between}
  .signin-lede{max-width:34ch;margin:clamp(22px,6vh,52px) 0}
  .signin-lede h1{font-size:clamp(27px,3.3vw,38px);line-height:1.16;color:#fff;font-weight:600}
  .signin-lede p{color:#B7DCC8;margin-top:14px}
  .signin-panel{background:var(--surface);display:flex;align-items:center;justify-content:center;padding:clamp(20px,4vw,46px)}
  .signin-card{width:100%;max-width:392px}
  .signin-card h2{color:var(--brand);margin-bottom:4px}
  .step-note{font-size:12.5px;color:var(--text-2);margin-bottom:14px}
  @media (max-width:880px){#signin{grid-template-columns:1fr}}
</style>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="{{ asset('assets/js/sldwp.js') }}"></script>
</body>
</html>
