<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('crumb', 'Dashboard'); ?>
<?php $__env->startSection('content'); ?>
  <div class="page__head">
    <h1>Dashboard</h1>
    <p>Signed in as <?php echo e(auth()->user()->name); ?>, <?php echo e(strtolower(auth()->user()->role?->name)); ?> for <?php echo e(auth()->user()->sector?->name); ?>. This view is assembled from the permissions your role holds.</p>
  </div>

  <div class="grid grid--kpi">
    <div class="kpi"><span>Active accounts</span><b><?php echo e($activeUsers); ?></b></div>
    <div class="kpi"><span>Documents you can open</span><b><?php echo e($visibleDocs); ?></b><em>of <?php echo e($heldDocs); ?> held</em></div>
    <div class="kpi <?php echo e($denials7d > 0 ? 'kpi--alert' : ''); ?>"><span>Denied requests, 7 days</span><b><?php echo e($denials7d); ?></b></div>
    <div class="kpi"><span>Notices for you</span><b><?php echo e($notices->count()); ?></b></div>
  </div>

  <div class="grid grid--split" style="margin-top:14px">
    <div class="card">
      <h3>Repository holdings by sector</h3>
      <canvas id="sectorChart" height="210" aria-label="Documents by sector and classification" role="img"></canvas>
      <p style="font-size:12.5px;color:var(--text-3);margin-top:10px">Documents held by each sector, split by classification.</p>
    </div>
    <div class="card">
      <h3>Latest notices</h3>
      <?php $__empty_1 = true; $__currentLoopData = $notices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--line)">
          <b><?php echo e($n->title); ?></b>
          <p style="font-size:13.5px;color:var(--text-2);margin:4px 0 0"><?php echo e(Str::limit($n->body, 120)); ?></p>
          <div style="font-size:12px;color:var(--text-3);margin-top:6px"><?php echo e($n->author->name); ?> · <?php echo e($n->published_at->format('j M Y')); ?></div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty"><b>No notices yet</b>Notices published by managers appear here.</div>
      <?php endif; ?>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
  var holdings = <?php echo json_encode($holdings, 15, 512) ?>;
  var sectors = [...new Set(holdings.map(h => h.sector))];
  function series(cls){ return sectors.map(s => (holdings.find(h => h.sector===s && h.classification===cls)||{}).total || 0); }
  var style = getComputedStyle(document.documentElement);
  new Chart(document.getElementById('sectorChart'), {
    type: 'bar',
    data: { labels: sectors, datasets: [
      { label: 'Public', data: series('public'), backgroundColor: '#8FC7AC' },
      { label: 'Internal', data: series('internal'), backgroundColor: '#14794A' },
      { label: 'Restricted', data: series('restricted'), backgroundColor: '#A8403A' }
    ]},
    options: { responsive:true, scales:{ x:{stacked:true, grid:{display:false}}, y:{stacked:true, beginAtZero:true, ticks:{precision:0}} },
      plugins:{ legend:{ position:'bottom' } } }
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\PHP\CAPSTONE_PROJECT\ST-DWP-MVP\resources\views/dashboard.blade.php ENDPATH**/ ?>