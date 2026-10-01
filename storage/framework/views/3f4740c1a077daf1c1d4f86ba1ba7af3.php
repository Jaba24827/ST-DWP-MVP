<?php $__env->startSection('title', 'Staff directory'); ?>
<?php $__env->startSection('crumb', 'Directory'); ?>
<?php $__env->startSection('content'); ?>
  <div class="page__head"><h1>Staff directory</h1><p>Contact details for staff across the Foundation's sites.</p></div>
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search by name">
    <select name="sector_id" onchange="this.form.submit()">
      <option value="">All sectors</option>
      <?php $__currentLoopData = $sectors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->id); ?>" <?php echo e((int)request('sector_id')===$s->id?'selected':''); ?>><?php echo e($s->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="btn btn--ghost btn--sm">Filter</button>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Position</th><th>Sector</th><th>Site</th><th>Email</th></tr></thead>
      <tbody>
      <?php $__empty_1 = true; $__currentLoopData = $people; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr><td><b><?php echo e($p->name); ?></b></td><td><?php echo e($p->position); ?></td><td><?php echo e($p->sector?->name); ?></td><td><?php echo e($p->site?->name); ?></td><td class="mono"><?php echo e($p->email); ?></td></tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="5" class="empty"><b>No one matches that search</b></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php echo e($people->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/directory/index.blade.php ENDPATH**/ ?>