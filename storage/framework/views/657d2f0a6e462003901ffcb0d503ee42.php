<?php $__env->startSection('title', 'Documents'); ?>
<?php $__env->startSection('crumb', 'Documents'); ?>
<?php $__env->startSection('content'); ?>
  <div class="page__head">
    <h1>Documents</h1>
    <p>Files are filtered by classification before the list is built, so you only see what your role permits.</p>
    @can_perm('documents.upload')
      <div class="page__actions" style="margin-top:14px">
        <button class="btn" onclick="document.getElementById('uploadForm').showModal()">Upload a document</button>
      </div>
    @endcan_perm
  </div>

  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    <label class="sr-only" for="q">Search documents</label>
    <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" placeholder="Search documents">
    <label class="sr-only" for="classification">Filter by classification</label>
    <select id="classification" name="classification" onchange="this.form.submit()">
      <option value="">All classifications</option>
      <?php $__currentLoopData = ['public','internal','restricted']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($c); ?>" <?php echo e(request('classification') === $c ? 'selected' : ''); ?>><?php echo e(ucfirst($c)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="btn btn--ghost btn--sm">Filter</button>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Document</th><th>Sector</th><th>Classification</th><th>Owner</th><th>Updated</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td><b><?php echo e($d->title); ?></b></td>
          <td><?php echo e($d->sector?->name); ?></td>
          <td><span class="tag <?php echo e($d->classification === 'restricted' ? 'tag--deny' : ($d->classification === 'internal' ? 'tag--warn' : 'tag--ok')); ?>"><?php echo e($d->classification); ?></span></td>
          <td><?php echo e($d->owner->name); ?></td>
          <td><?php echo e($d->created_at->format('j M Y')); ?></td>
          <td style="white-space:nowrap">
            <a class="btn btn--ghost btn--sm" href="<?php echo e(route('documents.download', $d)); ?>">Open</a>
            @can_perm('documents.delete')
              <form method="POST" action="<?php echo e(route('documents.destroy', $d)); ?>" style="display:inline" onsubmit="return confirm('Delete this document?')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn btn--ghost btn--sm">Delete</button>
              </form>
            @endcan_perm
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="6" class="empty"><b>Nothing to show</b>No document matches these filters within your access.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php echo e($documents->links()); ?>

  <p style="font-size:12.5px;color:var(--text-3);margin-top:10px">
    <?php if($hidden > 0): ?>
      <?php echo e($hidden); ?> restricted document<?php echo e($hidden > 1 ? 's are' : ' is'); ?> held in the repository but filtered out of the query for your role.
    <?php else: ?>
      Your role can open every classification held in the repository.
    <?php endif; ?>
  </p>

  <dialog id="uploadForm">
    <form method="POST" action="<?php echo e(route('documents.store')); ?>" enctype="multipart/form-data" style="padding:20px;min-width:min(420px,90vw)">
      <?php echo csrf_field(); ?>
      <h3 style="color:var(--brand)">Upload a document</h3>
      <p style="font-size:13.5px;color:var(--text-2)">Classification decides who can open the file.</p>
      <div class="field"><label for="title">Title</label><input id="title" name="title" required></div>
      <div class="field"><label for="sector_id">Sector</label>
        <select id="sector_id" name="sector_id">
          <?php $__currentLoopData = \App\Models\Sector::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s->id); ?>"><?php echo e($s->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select></div>
      <div class="field"><label for="classification">Classification</label>
        <select id="classification" name="classification" required>
          <option value="public">Public</option><option value="internal" selected>Internal</option><option value="restricted">Restricted</option>
        </select></div>
      <div class="field"><label for="file">File (PDF, Word, Excel or image, up to 10 MB)</label><input id="file" name="file" type="file" required></div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
        <button type="button" class="btn btn--ghost" onclick="document.getElementById('uploadForm').close()">Cancel</button>
        <button class="btn" type="submit">Upload</button>
      </div>
    </form>
  </dialog>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/documents/index.blade.php ENDPATH**/ ?>