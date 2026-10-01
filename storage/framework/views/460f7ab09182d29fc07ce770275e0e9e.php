<?php $__env->startSection('title', 'Sign in'); ?>
<?php $__env->startSection('lede', 'One place to reach the people, notices and files your site depends on.'); ?>
<?php $__env->startSection('content'); ?>
  <h2>Sign in</h2>
  <p class="step-note">Step 1 of 2 — your work address and password.</p>
  <form method="POST" action="<?php echo e(route('login')); ?>" novalidate>
    <?php echo csrf_field(); ?>
    <div class="field">
      <label for="email">Work email</label>
      <input id="email" name="email" type="email" autocomplete="username" required
             value="<?php echo e(old('email')); ?>" aria-invalid="<?php echo e($errors->has('email') ? 'true' : 'false'); ?>">
      <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="error-text" role="alert"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn--wide" type="submit" style="margin-top:18px">Continue</button>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/auth/login.blade.php ENDPATH**/ ?>