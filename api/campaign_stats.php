<?php

    require '../config/database.php';

    $campaign_id = (int) $_GET['campaign_id'];
    $stmt = $pdo->prepare("
        SELECT 
            SUM(status = 'sent') AS sent,
            SUM(status = 'pending') AS pending,
            SUM(status = 'failed') AS failed
        FROM campaign_recipients
        WHERE campaign_id = ?
    ");
    $stmt->execute([$campaign_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));