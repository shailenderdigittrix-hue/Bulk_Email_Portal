<?php

session_start();

require 'config/database.php';

if (!isset($_GET['id'])) {
    die('Template ID missing');
}

$id = (int) $_GET['id'];

// Fetch Template
$query = $pdo->prepare("
    SELECT *
    FROM email_templates
    WHERE id = ?
");

$query->execute([$id]);

$template = $query->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    die('Template not found');
}


// Update Template
if (isset($_POST['update'])) {

    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    $update = $pdo->prepare("
        UPDATE email_templates
        SET
            title = ?,
            subject = ?,
            body = ?
        WHERE id = ?
    ");

    $update->execute([
        $title,
        $subject,
        $body,
        $id
    ]);

    $_SESSION['success'] =
        "Template updated successfully";

    header("Location: templates.php");
    exit;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Edit Template</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4>Edit Template</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">
                            Template Title
                            </label>
                            <input type="text"
                                name="title"
                                class="form-control"
                                value="<?= htmlspecialchars($template['title']) ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                            Email Subject
                            </label>
                            <input type="text"
                                name="subject"
                                class="form-control"
                                value="<?= htmlspecialchars($template['subject']) ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                            Email Body
                            </label>
                            <textarea name="body"
                                class="form-control"
                                rows="8"
                                required><?= htmlspecialchars($template['body']) ?></textarea>
                        </div>
                        <button type="submit"
                            name="update"
                            class="btn btn-success">
                        Update Template
                        </button>
                        <a href="templates.php"
                            class="btn btn-secondary">
                        Back
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>