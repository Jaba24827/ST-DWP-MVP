<?php $__env->startSection('title', 'Roles and permissions'); ?>
<?php $__env->startSection('crumb', 'Roles'); ?>
<?php $__env->startSection('content'); ?>
  <div class="page__head">
    <h1>Roles and permissions</h1>
    <p>This grid is the authorisation source. Every screen and every action reads from it.</p>
  </div>
  <div class="table-wrap">
    <table class="matrix">
      <thead><tr><th>Permission</th><?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th><?php echo e($role->name); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr></thead>
      <tbody>
      <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
          <td><span class="mono"><?php echo e($perm->key); ?></span><br><span style="font-size:12px;color:var(--text-3)"><?php echo e($perm->description); ?></span></td>
          <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <td style="text-align:center">
              <form action="<?php echo e(route('roles.update', $role)); ?>" method="POST">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <?php $__currentLoopData = $role->permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <?php if($p->id !== $perm->id): ?><input type="hidden" name="permissions[]" value="<?php echo e($p->id); ?>"><?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <input type="checkbox" name="permissions[]" value="<?php echo e($perm->id); ?>"
                       <?php echo e($role->permissions->contains('id', $perm->id) ? 'checked' : ''); ?>

                       aria-label="<?php echo e($role->name); ?> holds <?php echo e($perm->key); ?>">
              </form>
            </td>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <p style="font-size:12.5px;color:var(--text-3);margin-top:10px">
    Least privilege: a role holds only the permissions its work requires. An administrator cannot remove
    <span class="mono">roles.manage</span> from their own role — that guard sits in the controller, not in this page.
  </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/roles/index.blade.php ENDPATH**/ ?>