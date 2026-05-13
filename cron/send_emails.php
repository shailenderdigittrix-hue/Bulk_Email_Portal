<?php

require '../config/database.php';
require '../functions.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 1. Get only valid recipients
 * - pending or failed
 * - retry < 3
 * - campaign is due (for scheduled campaigns)
 */
$recipients = $pdo->query("
    SELECT
        cr.*,
        c.name,
        c.company,
        et.subject,
        et.body,
        cp.id as campaign_id,
        cp.send_type,
        cp.scheduled_at
    FROM campaign_recipients cr
    JOIN contacts c ON c.id = cr.contact_id
    JOIN campaigns cp ON cp.id = cr.campaign_id
    JOIN email_templates et ON et.id = cp.template_id
    WHERE cr.status IN ('pending','failed')
    AND cr.retry_count < 3
    AND (
        cp.send_type = 'now'
        OR (cp.send_type = 'scheduled' AND cp.scheduled_at <= NOW())
    )
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
/** here i have set LIMIT 3 but increese later as per requirement **/

foreach ($recipients as $recipient) {
    try {

        // ------------------------
        // LOCK (avoid duplicate send)
        // ------------------------
        $lock = $pdo->prepare("
            UPDATE campaign_recipients
            SET status = 'processing'
            WHERE id = ? AND status IN ('pending','failed')
        ");

        $lock->execute([$recipient['id']]);

        if ($lock->rowCount() == 0) {
            continue; // already processed by another cron run
        }

        // ------------------------
        // MAILER SETUP
        // ------------------------
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        
        $mail->Username   = 'shailender.digittrix@gmail.com';
        $mail->Password   = 'mckb msrn brdn qgqn';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('shailender.digittrix@gmail.com', 'Bulk Email Portal', );
        $mail->addAddress($recipient['email'], $recipient['name']);

        // ------------------------
        // TEMPLATE REPLACEMENT
        // ------------------------
        $body = replaceTemplateVariables($recipient['body'], [
            'name'    => $recipient['name'],
            'company' => $recipient['company']
        ]);

        $mail->isHTML(true);
        $mail->Subject = $recipient['subject'];
        $mail->Body    = $body;

        // ------------------------
        // SEND EMAIL
        // ------------------------
        $mail->send();

        // ------------------------
        // SUCCESS UPDATE
        // ------------------------
        $pdo->prepare("
            UPDATE campaign_recipients
            SET status='sent',
                sent_at=NOW()
            WHERE id=?
        ")->execute([$recipient['id']]);

        $pdo->prepare("
            INSERT INTO email_logs(
                campaign_id,
                email,
                status,
                message
            ) VALUES(?,?,?,?)
        ")->execute([
            $recipient['campaign_id'],
            $recipient['email'],
            'success',
            'Email sent successfully'
        ]);

        echo "Sent: {$recipient['email']}\n";

    } catch (Exception $e) {

        // ------------------------
        // FAILED + RETRY LOGIC
        // ------------------------
        $pdo->prepare("
            UPDATE campaign_recipients
            SET retry_count = retry_count + 1,
                status = CASE
                    WHEN retry_count + 1 >= 3 THEN 'failed'
                    ELSE 'pending'
                END
            WHERE id = ?
        ")->execute([$recipient['id']]);

        $pdo->prepare("
            INSERT INTO email_logs(
                campaign_id,
                email,
                status,
                message
            ) VALUES(?,?,?,?)
        ")->execute([
            $recipient['campaign_id'],
            $recipient['email'],
            'failed',
            $e->getMessage()
        ]);

        echo "Failed: {$recipient['email']}\n";
    }
}
$campaigns = $pdo->query("
    SELECT DISTINCT campaign_id 
    FROM campaign_recipients
")->fetchAll(PDO::FETCH_COLUMN);

foreach ($campaigns as $campaignId) {
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM campaign_recipients
        WHERE campaign_id = ?
        AND status != 'sent'
    ");
    $check->execute([$campaignId]);
    $remaining = $check->fetchColumn();
    if ($remaining == 0) {
        $pdo->prepare("
            UPDATE campaigns
            SET status = 'completed'
            WHERE id = ?
        ")->execute([$campaignId]);
    }
}
echo "Bulk email processing completed.";