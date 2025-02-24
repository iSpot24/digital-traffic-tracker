<?php

include(__DIR__ . '/../app/classes/Helpers.php');

$db = \App\Classes\Helpers::getDbConnection();
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

while (true) {
    $data = $redis->lPop('tracker_queue');

    if ($data) {
        $data = json_decode($data, true);
        $query = $db->prepare("INSERT INTO trackings (client_id, url, tracked_id, created_at) values (?,?,?,?)");
        $query->bind_param("isss", $data['clientId'], $data['pageUrl'], $data['trackedId'], $data['timestamp']);

        if (!$query->execute()) {
            error_log("Database error: ", $query->error);
        }
        $query->close();
    } else {
        sleep(1);
    }
}
