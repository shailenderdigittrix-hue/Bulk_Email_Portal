<?php

require '../config/database.php';

$SENDGRID_API_KEY = "YOUR_SENDGRID_API_KEY";

// get batch emails
$stmt = $pdo->prepare("
    SELECT *
    FROM campaign_recipients
    WHERE status IN ('pending','failed')
    AND retry_count < 3
    LIMIT 50
");

$stmt->execute();
$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($emails as $row) {

    $id = $row['id'];

    try {
        // -------- SENDGRID API CALL ----------------
        $payload = [
            "personalizations" => [[
                "to" => [[
                    "email" => $row['email']
                ]]
            ]],
            "from" => [
                "email" => "your_email@example.com"
            ],
            "subject" => "Bulk Email Campaign",
            "content" => [[
                "type" => "text/html",
                "value" => "<h3>Hello!</h3><p>This is a bulk email.</p>"
            ]]
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.sendgrid.com/v3/mail/send");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $SENDGRID_API_KEY",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        // --------------- SUCCESS -----------------------
        if ($httpCode == 202) {
            $pdo->prepare("
                UPDATE campaign_recipients
                SET status = 'sent',
                    sent_at = NOW()
                WHERE id = ?
            ")->execute([$id]);

            $pdo->prepare("
                INSERT INTO email_logs (
                    campaign_id,
                    email,
                    status,
                    message
                ) VALUES (?, ?, ?, ?)
            ")->execute([
                $row['campaign_id'],
                $row['email'],
                'sent',
                'Email sent successfully'
            ]);
        } else {
            throw new Exception($response);
        }
    } catch (Exception $e) {
        // -------------- RETRY SYSTEM ---------------------
        $pdo->prepare("
            UPDATE campaign_recipients
            SET retry_count = retry_count + 1,
                status = CASE 
                    WHEN retry_count + 1 >= 3 THEN 'failed'
                    ELSE 'pending'
                END
            WHERE id = ?
        ")->execute([$id]);

        $pdo->prepare("
            INSERT INTO email_logs (
                campaign_id,
                email,
                status,
                message
            ) VALUES (?, ?, ?, ?)
        ")->execute([
            $row['campaign_id'],
            $row['email'],
            'failed',
            $e->getMessage()
        ]);
    }
}