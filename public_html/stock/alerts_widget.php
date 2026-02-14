<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';
 if(count($alertes)): ?>
<div style="background:#fff3cd;padding:10px;">
<b>⚠️ Alertes stock</b>
<ul>
<?php foreach($alertes as $a): ?>
<li><?= $a['designation'] ?> : <?= $a['quantite'] ?> restants</li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>
