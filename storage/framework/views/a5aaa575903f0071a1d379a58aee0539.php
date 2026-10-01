<?php $__env->startSection('title', 'Notices'); ?>
<?php $__env->startSection('crumb', 'Notices'); ?>
<?php $__env->startSection('content'); ?>
  <div class="page__head">
    <h1>Notices</h1><p>Notices published to your sector and to the whole Foundation.</p>
    @can_perm('announcements.publish')
      <div class="page__actions" style="margin-top:14px">
        <button class="btn" onclick="document.getElementById('newNotice').showModal()">Publish a notice</button>
      </div>
    @endcan_perm
  </div>
  <div class="card">
    <?php $__empty_1 = true; $__currentLoopData = $notices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div style="padding:14px 0;border-bottom:1px solid var(--line)">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <b><?php echo e($n->title); ?></b><span class="tag"><?php echo e($n->audience); ?></span>
          @can_perm('announcements.publish')
            <form method="POST" action="<?php echo e(route('notices.destroy', $n)); ?>" style="margin-left:auto" onsubmit="return confirm('Withdraw this notice?')">
              <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn--ghost btn--sm">Withdraw</button>
            </form>
          @endcan_perm
        </div>
        <p style="font-size:13.5px;color:var(--text-2)"><?php echo e($n->body); ?></p>
        <div style="font-size:12px;color:var(--text-3)"><?php echo e($n->author->name); ?> · <?php echo e($n->published_at->format('j M Y')); ?></div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="empty"><b>Nothing published yet</b></div>
    <?php endif; ?>
  </div>
  <?php echo e($notices->links()); ?>


  <dialog id="newNotice">
    <form method="POST" action="<?php echo e(route('notices.store')); ?>" style="padding:20px;min-width:min(440px,90vw)">
      <?php echo csrf_field(); ?>
      <h3 style="color:var(--brand)">Publish a notice</h3>
      <div class="field"><label for="title">Title</label><input id="title" name="title" required></div>
      <div class="field"><label for="body">Message</label><textarea id="body" name="body" rows="4" required></textarea></div>
      <div class="field"><label for="audience">Audience</label>
        <select id="audience" name="audience">
          <option value="all">All sectors</option>
          <?php $__currentLoopData = $sectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->name); ?>"><?php echo e($s->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button type="button" class="btn btn--ghost" onclick="document.getElementById('newNotice').close()">Cancel</button>
        <button class="btn" type="submit">Publish</button>
      </div>
    </form>
  </dialog>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/announcements/index.blade.php ENDPATH**/ ?>