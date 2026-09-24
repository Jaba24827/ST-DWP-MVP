<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · SL-DWP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/sldwp.css'])
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<div class="app" id="appShell">
  <div class="scrim"></div>
  @include('partials.sidebar')

  <div class="main">
    @include('partials.topbar')

    <div class="content" id="main">
      <div class="page">
        @if (session('status'))
          <div class="flash flash--ok" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
          <div class="flash flash--error" role="alert">{{ $errors->first() }}</div>
        @endif

        @yield('content')
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="{{ asset('assets/js/sldwp.js') }}"></script>
@stack('scripts')
</body>
</html>
