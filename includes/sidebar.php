<?php $current = $page ?? 'overview'; ?>
<aside class="sidebar">
  <a class="brand" href="<?= page_url('overview') ?>"><img src="/assets/img/logo-white.svg" alt="Oplen"><span>ADMIN</span></a>
  <nav>
    <p>CONTROL CENTER</p><a class="<?= $current==='overview'?'active':'' ?>" href="<?= page_url('overview') ?>">Overview</a>
    <p>COMMERCIAL</p><a class="<?= $current==='leads'?'active':'' ?>" href="<?= page_url('leads') ?>">Leads</a><a class="<?= $current==='pipeline'?'active':'' ?>" href="<?= page_url('pipeline') ?>">Pipeline</a>
    <p>CUSTOMERS</p><a class="<?= $current==='organizations'?'active':'' ?>" href="<?= page_url('organizations') ?>">Organizations</a><a class="<?= $current==='users'?'active':'' ?>" href="<?= page_url('users') ?>">Users</a>
    <p>OPLEN</p><a class="<?= $current==='team'?'active':'' ?>" href="<?= page_url('team') ?>">Internal team</a><a class="<?= $current==='roles'?'active':'' ?>" href="<?= page_url('roles') ?>">Roles & access</a>
    <p>BUSINESS</p><a class="<?= $current==='billing'?'active':'' ?>" href="<?= page_url('billing') ?>">Billing</a>
    <p>SYSTEM</p><a class="<?= $current==='system'?'active':'' ?>" href="<?= page_url('system') ?>">System health</a><a class="<?= $current==='settings'?'active':'' ?>" href="<?= page_url('settings') ?>">Settings</a>
  </nav>
  <footer>
    <div style="margin-bottom:14px"><strong style="display:block;color:#f1f1ed;font-size:11px"><?= e(trim(($admin['first_name'] ?? '').' '.($admin['last_name'] ?? '')) ?: ($admin['email'] ?? 'Admin')) ?></strong><span style="display:block;color:#8a8a93;font-size:10px;margin:4px 0 7px"><?= e($admin['role_name'] ?? 'Admin') ?></span><a href="logout.php" style="color:#c7ff35;font-size:10px">Sign out</a></div>
    <span class="status-dot"></span>Operational<small>Last update<br><?= OPLEN_LAST_UPDATE ?></small>
  </footer>
</aside>