<?php
$data = file_get_contents("diesel.json");
$json = json_decode($data, true);
echo json_last_error_msg();

