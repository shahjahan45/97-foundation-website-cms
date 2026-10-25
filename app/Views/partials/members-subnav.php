<?php
$membersTab = $membersTab ?? 'directory';
$membersLinks = [
    'directory' => ['Members Directory', 'members'],
    'officials' => ['Officials', 'members/officials'],
    'past' => ['Past Officials', 'members/past-officials'],
];
?>
<nav class="members-subnav7 container" aria-label="Members sections"><ul>
<?php foreach ($membersLinks as $key => [$label, $path]): ?>
  <li><a class="<?= $membersTab === $key ? 'is-active' : '' ?>" href="<?= url($path) ?>" data-spa-link<?= $membersTab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if (isset($membersCounts[$key])): ?><em><?= (int)$membersCounts[$key] ?></em><?php endif; ?></a></li>
<?php endforeach; ?>
</ul></nav>
