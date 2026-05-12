<?php

session_start();

require 'config/database.php';
require 'functions.php';


// Create Campaign
if (isset($_POST['create_campaign'])) {

    $name = trim($_POST['name']);
    $template_id = (int) $_POST['template_id'];
    $send_type = trim($_POST['send_type']);
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;

    try {
        $status = ($send_type === 'now') ? 'processing' : 'scheduled';
        $insert = $pdo->prepare("
            INSERT INTO campaigns(
                name,
                template_id,
                status,
                send_type,
                scheduled_at
            ) VALUES(?,?,?,?,?)
        ");

        $insert->execute([
            $name,
            $template_id,
            $status,
            $send_type,
            $scheduled_at
        ]);

        $_SESSION['success'] = "Campaign created successfully";
        header("Location: campaigns.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Campaign creation failed";
        header("Location: campaigns.php");
        exit;
    }
}

// Fetch Templates
$templates = $pdo->query("
    SELECT *
    FROM email_templates
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$limit = 5; // Pagination.  Change limit accordingly
$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;
$totalRows = $pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();   // Total Campaigns
$totalPages = ceil($totalRows / $limit);

// Fetch Campaigns
$campaignQuery = $pdo->prepare("
    SELECT c.*, et.title as template_name
    FROM campaigns c
    LEFT JOIN email_templates et
    ON et.id = c.template_id
    ORDER BY c.id DESC
    LIMIT :limit OFFSET :offset
");

$campaignQuery->bindValue(':limit', $limit, PDO::PARAM_INT);
$campaignQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
$campaignQuery->execute();
$campaigns = $campaignQuery->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Campaigns</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h2 class="mb-4">Campaign Management</h2>
            <a href="index.php" style="text-decoration:none">Back</a>
            <!-- Flash Messages -->
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"> <?= $_SESSION['success']; ?> </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"> <?= $_SESSION['error']; ?> </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <!-- Create Campaign -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Create Campaign</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">
                            Campaign Name
                            </label>
                            <input type="text"
                                name="name"
                                class="form-control"
                                placeholder="Enter Campaign Name"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                            Select Template
                            </label>
                            <select name="template_id"
                                class="form-control"
                                required>
                                <option value=""> Select Template </option>
                                <?php foreach ($templates as $template): ?>
                                <option value="<?= $template['id'] ?>">
                                    <?= htmlspecialchars($template['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Send type</label>
                            <select name="send_type" class="form-control" id="send_type">
                                <option value="now">Send Now</option>
                                <option value="scheduled">Schedule</option>
                            </select>
                        </div>
                        <div class="mb-3" id="schedule_box" style="display:none; margin-top:10px;">
                            <input type="datetime-local" name="scheduled_at" class="form-control">
                        </div>
                        <button type="submit"
                            name="create_campaign"
                            class="btn btn-primary">
                        Create Campaign
                        </button>
                    </form>
                </div>
            </div>
            <!-- Campaign List -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Campaign List</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Campaign Name</th>
                                <th>Template</th>
                                <th>Status</th>
                                <th>Schedule</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($campaigns): ?>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <?= $campaign['id'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($campaign['name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($campaign['template_name']) ?>
                                </td>
                                <td>
                                    <?php if($campaign['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">
                                    Pending
                                    </span>
                                    <?php elseif($campaign['status'] == 'processing'): ?>
                                    <span class="badge bg-primary">
                                    Processing
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-success">
                                    Completed
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($campaign['scheduled_at'])): ?>
                                    <span class="badge bg-info">
                                    <?= date('d M Y H:i', strtotime($campaign['scheduled_at'])) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $campaign['created_at'] ?>
                                </td>
                                <td>
                                    <?php if($campaign['status'] == 'pending'): ?>
                                    <a href="send_campaign.php?id=<?= $campaign['id'] ?>"
                                        class="btn btn-success btn-sm">
                                    Queue Emails
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                    Already Queued
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">
                                    No Campaigns Found
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
                                <a class="page-link"
                                    href="?page=<?= $page - 1 ?>">
                                Previous
                                </a>
                            </li>
                            <?php endif; ?>
                            <!-- Page Numbers -->
                            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?page=<?= $i ?>">
                                <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            <!-- Next -->
                            <?php if($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?= $page + 1 ?>">
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
            $(document).ready(function () {
                $('#send_type').on('change', function () {
                    if ($(this).val() === 'scheduled') {
                        $('#schedule_box').show();
                    } else {
                        $('#schedule_box').hide();
                    }
                });
       
            });
        </script>
        <script>
            setInterval(function () {
                $.ajax({
                    url: 'cron/send_emails.php',
                    type: 'GET',
                    success: function (response) {
                        console.log('Worker executed');
                        console.log(response);
                    },
                    error: function () {
                        console.log('Worker failed');
                    }
                });
            }, 10000); // every 10 seconds Add/remove accordingly
        </script>
    </body>
</html>