<?php

$brandMarkSize = isset($brandMarkSize) ? (int) $brandMarkSize : 36;
$brandMarkClass = trim('brand-mark ' . ($brandMarkClass ?? ''));


$isAdmin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;


$uploadedPath = __DIR__ . '/../assets/images/uploads/cvsu.png';
if (file_exists($uploadedPath)) {
    $logoPath = '/assets/images/uploads/cvsu.png';
} else {
    $logoPath = $isAdmin ? '/assets/images/cvsu-logo.svg' : '/assets/images/logo-mark.svg';
}
?>
<span class="<?= e($brandMarkClass) ?>" style="width:<?= $brandMarkSize ?>px;height:<?= $brandMarkSize ?>px" aria-hidden="true">
  <img src="<?= SITE_URL ?><?= $logoPath ?>" alt="" width="<?= $brandMarkSize ?>" height="<?= $brandMarkSize ?>" decoding="async">
</span>
