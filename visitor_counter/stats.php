<?php
include '../../../plugins/visitor_counter.php';
header('Content-Type: application/json');
echo json_encode(visitor_counter_get_stats());
?>
