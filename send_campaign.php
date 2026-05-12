<?php

require 'config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Campaign ID is required');
}

$campaign_id = (int) $_GET['id'];

try {

    /**  1. Get campaign */
    $campaignStmt = $pdo->prepare("
        SELECT *
        FROM campaigns
        WHERE id = ?
    ");

    $campaignStmt->execute([$campaign_id]);

    $campaign = $campaignStmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaign) {
        die('Campaign not found');
    }

    /**  2. Prevent duplicate processing */
    if ($campaign['status'] == 'processing') {
        die('Campaign already queued');
    }

    if ($campaign['status'] == 'completed') {
        die('Campaign already completed');
    }

    /** 3. Check scheduled campaign  */
    if (
        $campaign['send_type'] == 'scheduled'
        && !empty($campaign['scheduled_at'])
    ) {

        $now = date('Y-m-d H:i:s');

        if ($campaign['scheduled_at'] > $now) {

            die(
                'This campaign is scheduled for: '
                . $campaign['scheduled_at']
            );
        }
    }

    /**  4. Get all contacts */
    $contacts = $pdo->query("
        SELECT *
        FROM contacts
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!$contacts) {
        die('No contacts found');
    }

    /**  5. Existing recipients */
    $existingStmt = $pdo->prepare("
        SELECT contact_id
        FROM campaign_recipients
        WHERE campaign_id = ?
    ");

    $existingStmt->execute([$campaign_id]);

    $existingIds = array_flip(
        array_column(
            $existingStmt->fetchAll(PDO::FETCH_ASSOC),
            'contact_id'
        )
    );

    /**  6. Queue insert statement */
    $insertRecipient = $pdo->prepare("
        INSERT INTO campaign_recipients (
            campaign_id,
            contact_id,
            email,
            status
        ) VALUES (?, ?, ?, ?)
    ");

    /**  7. Log statement */
    $logStmt = $pdo->prepare("
        INSERT INTO email_logs (
            campaign_id,
            email,
            status,
            message
        ) VALUES (?, ?, ?, ?)
    ");

    /**  8. Insert contacts into queue */
    foreach ($contacts as $contact) {

        /**  Prevent duplicate queue records */
        if (!isset($existingIds[$contact['id']])) {

            $insertRecipient->execute([
                $campaign_id,
                $contact['id'],
                $contact['email'],
                'pending'
            ]);

            /** Add log */
            $logStmt->execute([
                $campaign_id,
                $contact['email'],
                'queued',
                'Email added to queue'
            ]);
        }
    }

    /**  9. Update campaign status */
    $updateCampaign = $pdo->prepare("
        UPDATE campaigns
        SET status = 'processing'
        WHERE id = ?
    ");

    $updateCampaign->execute([$campaign_id]);

    /**  10. Redirect */
    header(
        "Location: campaigns.php?success=Campaign queued successfully"
    );
    exit;

} catch (Exception $e) {
    die(
        'Error: ' . $e->getMessage()
    );
}