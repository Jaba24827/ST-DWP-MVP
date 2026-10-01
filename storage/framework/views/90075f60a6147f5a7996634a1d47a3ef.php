<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> · SL-DWP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php echo app('Illuminate\Foundation\Vite')(['resources/css/sldwp.css']); ?>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>

<div class="app" id="appShell">
  <div class="scrim"></div>
  <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <div class="main">
    <?php echo $__env->make('partials.topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="content" id="main">
      <div class="page">
        <?php if(session('status')): ?>
          <div class="flash flash--ok" role="status"><?php echo e(session('status')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
          <div class="flash flash--error" role="alert"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="<?php echo e(asset('assets/js/sldwp.js')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/layouts/app.blade.php ENDPATH**/ ?>