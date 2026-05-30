<?php
$hash = '$2y$12$NJUyfqs/0ZDjoSEU.bxDBuTh9nN2q9F/ZeDr1.PdXa56vn0kml7cq';
foreach (['password','admin','admin123','CvSU2026','Password123'] as $pwd) {
    echo $pwd . ': ' . (password_verify($pwd, $hash) ? 'MATCH' : 'NO') . "\n";
}
