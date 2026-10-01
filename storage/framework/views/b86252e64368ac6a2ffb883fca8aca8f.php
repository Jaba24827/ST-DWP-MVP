<?php $__env->startSection('title', 'Verify'); ?>
<?php $__env->startSection('lede', 'Confirm it is you.'); ?>
<?php $__env->startSection('content'); ?>
  <h2>Confirm it is you</h2>
  <p class="step-note">Step 2 of 2 — a six-digit code, valid for five minutes.</p>

  <?php if(! $sent): ?>
    <p style="font-size:13.5px;color:var(--text-2)">Where should the code go?</p>
    <form method="POST" action="<?php echo e(route('mfa.send')); ?>">
      <?php echo csrf_field(); ?>
      <div style="display:flex;gap:8px;margin:10px 0 16px">
        <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid var(--line);border-radius:6px;flex:1">
          <input type="radio" name="channel" value="email" <?php echo e($channel === 'email' ? 'checked' : ''); ?>> Email <span class="mono"><?php echo e($emailMask); ?></span>
        </label>
        <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid var(--line);border-radius:6px;flex:1">
          <input type="radio" name="channel" value="sms" <?php echo e($channel === 'sms' ? 'checked' : ''); ?>> SMS <span class="mono"><?php echo e($smsMask); ?></span>
        </label>
      </div>
      <button class="btn btn--wide" type="submit">Send the code</button>
    </form>
  <?php else: ?>
    <form method="POST" action="<?php echo e(route('mfa.verify')); ?>">
      <?php echo csrf_field(); ?>
      <div class="field">
        <label for="code">Six-digit code</label>
        <input id="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
               style="letter-spacing:.4em;font-family:'IBM Plex Mono',monospace;font-size:19px;text-align:center" required>
        <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="error-text" role="alert"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <button id="verifySubmit" class="btn btn--wide" type="submit" style="margin-top:18px">Verify and sign in</button>
    </form>
    <form method="POST" action="<?php echo e(route('mfa.send')); ?>" style="margin-top:12px">
      <?php echo csrf_field(); ?><input type="hidden" name="channel" value="<?php echo e($channel); ?>">
      <button class="btn btn--ghost btn--sm" type="submit">Send a new code</button>
    </form>
  <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/auth/mfa.blade.php ENDPATH**/ ?>