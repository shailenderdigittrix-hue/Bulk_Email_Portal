<?php
    // error_reporting(E_ALL);
    // ini_set('display_errors', 1);

    session_start();

    require 'config/database.php';

    // Dashboard Counts
    $totalContacts = $pdo->query("
        SELECT COUNT(*) FROM contacts
    ")->fetchColumn();

    $totalTemplates = $pdo->query("
        SELECT COUNT(*) FROM email_templates
    ")->fetchColumn();

    $totalCampaigns = $pdo->query("
        SELECT COUNT(*) FROM campaigns
    ")->fetchColumn();


    // Pagination 
    $limit = 5;
    $page = isset($_GET['page'])
        ? (int) $_GET['page']
        : 1;

    if ($page < 1) {
        $page = 1;
    }

    $offset = ($page - 1) * $limit;

    // Total Records
    $totalRows = $pdo->query("
        SELECT COUNT(*) FROM contacts
    ")->fetchColumn();

    $totalPages = ceil($totalRows / $limit);

    // Fetch Contacts
    $contactsQuery = $pdo->prepare("
        SELECT *
        FROM contacts
        ORDER BY id DESC
        LIMIT :limit OFFSET :offset
    ");

    $contactsQuery->bindValue(':limit', $limit, PDO::PARAM_INT);
    $contactsQuery->bindValue(':offset', $offset, PDO::PARAM_INT);

    $contactsQuery->execute();

    $contacts = $contactsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Bulk Email Portal</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
        <h2 class="mb-4">Bulk Email Management Portal</h2>
        <!-- Flash Messages -->
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success']; ?>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; ?>
        </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <!-- Dashboard Cards -->
        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <div class="card p-3 shadow-sm">
                    <h4>Total Contacts</h4>
                    <h2><?= $totalContacts ?></h2>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <a href="templates.php" style="text-decoration:none">
                    <div class="card p-3 shadow-sm">
                        <h4>Total Templates</h4>
                        <h2><?= $totalTemplates ?></h2>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card p-3 shadow-sm d-flex flex-row justify-content-between align-items-center">
                    <!-- Left: Campaigns -->
                    <a href="campaigns.php" style="text-decoration:none; color:inherit;">
                        <div>
                            <h4>Total Campaigns</h4>
                            <h2><?= $totalCampaigns ?></h2>
                        </div>
                    </a>
                    <!-- Right: Stats -->
                    <div style="text-align:right;">
                        Sent: <span id="sent">0</span><br>
                        Pending: <span id="pending">0</span><br>
                        Failed: <span id="failed">0</span>
                    </div>
                </div>
            </div>
            <!-- Buttons -->
            <div class="mt-4 mb-4">
                <a href="upload_contacts.php" class="btn btn-primary">
                Upload Contacts
                </a>
                <!-- <a href="templates.php" class="btn btn-success">
                    Manage Templates
                    </a>
                    
                    <a href="campaigns.php" class="btn btn-dark">
                    Campaigns
                    </a> -->
            </div>
            <!-- Contacts Table -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">All Contacts</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($contacts): ?>
                            <?php foreach($contacts as $contact): ?>
                            <tr>
                                <td><?= $contact['id']; ?></td>
                                <td><?= htmlspecialchars($contact['name']); ?></td>
                                <td><?= htmlspecialchars($contact['email']); ?></td>
                                <td><?= htmlspecialchars($contact['company']); ?></td>
                                <td><?= $contact['created_at']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    No Contacts Found
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination">
                            <!-- Previous -->
                            <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1; ?>">
                                Previous
                                </a>
                            </li>
                            <?php endif; ?>
                            <!-- Page Numbers -->
                            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?= $i; ?>">
                                <?= $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            <!-- Next -->
                            <?php if($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?= $page + 1; ?>">
                                Next
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script>
            function loadStats() {
                $.get("api/campaign_stats.php?campaign_id=1", function(data) {
                    let res = JSON.parse(data);
                    $("#sent").text(res.sent);
                    $("#pending").text(res.pending);
                    $("#failed").text(res.failed);
                });
            }

            // refresh every 3 seconds
            setInterval(loadStats, 3000);
            loadStats();
        </script>
    </body>
</html>