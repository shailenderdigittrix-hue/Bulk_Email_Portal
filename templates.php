<?php

session_start();

require 'config/database.php';


// Save Template
if (isset($_POST['save'])) {

    $title = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    if (
        empty($title) ||
        empty($subject) ||
        empty($body)
    ) {

        $_SESSION['error'] =
            "All fields are required";

        header("Location: templates.php");
        exit;
    }

    try {

        $insert = $pdo->prepare("
            INSERT INTO email_templates(
                title,
                subject,
                body
            ) VALUES(?,?,?)
        ");

        $insert->execute([
            $title,
            $subject,
            $body
        ]);

        $_SESSION['success'] =
            "Template added successfully";

        header("Location: templates.php");
        exit;

    } catch (Exception $e) {

        $_SESSION['error'] =
            "Template creation failed";

        header("Location: templates.php");
        exit;
    }
}


// Pagination
$limit = 5;

$page = isset($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;


// Total Templates
$totalRows = $pdo->query("
    SELECT COUNT(*)
    FROM email_templates
")->fetchColumn();

$totalPages = ceil($totalRows / $limit);


// Fetch Templates
$templateQuery = $pdo->prepare("
    SELECT *
    FROM email_templates
    ORDER BY id DESC
    LIMIT :limit OFFSET :offset
");

$templateQuery->bindValue(
    ':limit',
    $limit,
    PDO::PARAM_INT
);

$templateQuery->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$templateQuery->execute();

$templates = $templateQuery->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Email Templates</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<div class="container mt-5">

    <h2 class="mb-4">
        Email Template Management
    </h2>
    <a href="index.php" style="text-decoration:none">Back</a>


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


    <!-- Create Template -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <h4 class="mb-0">
                Create Template
            </h4>
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
                           placeholder="Enter Template Title"
                           required>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Email Subject
                    </label>

                    <input type="text"
                           name="subject"
                           class="form-control"
                           placeholder="Enter Email Subject"
                           required>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Email Body
                    </label>

                    <textarea name="body"
                              id="editor"
                              class="form-control"
                              rows="8"
                              placeholder="Write Email Content"
                              required></textarea>

                </div>


                <div class="alert alert-info">

                    Available Variables:
                    <br>

                    <strong>{{name}}</strong>
                    <br>

                    <strong>{{company}}</strong>
                    <br>

                    <strong>{{email}}</strong>

                </div>


                <button type="submit"
                        name="save"
                        class="btn btn-success">

                    Save Template

                </button>

            </form>

        </div>

    </div>


    <!-- Template List -->

    <div class="card shadow-sm">

        <div class="card-header">
            <h4 class="mb-0">
                Saved Templates
            </h4>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Body Preview</th>
                    <th>Created At</th>
                    <th width="180">Action</th>
                </tr>

                </thead>

                <tbody>

                <?php if($templates): ?>

                    <?php foreach ($templates as $template): ?>

                        <tr>

                            <td>
                                <?= $template['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($template['title']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($template['subject']) ?>
                            </td>

                            <td>

                                <?= substr(
                                    strip_tags($template['body']),
                                    0,
                                    80
                                ) ?>...

                            </td>

                            <td>
                                <?= $template['created_at'] ?>
                            </td>

                            <td>

                                <!-- Edit Button -->

                                <a href="template_edit.php?id=<?= $template['id'] ?>"
                                class="btn btn-primary btn-sm">

                                    Edit

                                </a>


                                <!-- Delete Button -->

                                <!-- <a href="delete_template.php?id=<?= $template['id'] ?>"
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this template?')">

                                    Delete

                                </a> -->

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" class="text-center">

                            No Templates Found

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>    


            <!-- Pagination -->

            <nav>

                <ul class="pagination">

                    <?php if($page > 1): ?>

                        <li class="page-item">

                            <a class="page-link"
                               href="?page=<?= $page - 1 ?>">

                                Previous

                            </a>

                        </li>

                    <?php endif; ?>


                    <?php for($i = 1; $i <= $totalPages; $i++): ?>

                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

                            <a class="page-link"
                               href="?page=<?= $i ?>">

                                <?= $i ?>

                            </a>

                        </li>

                    <?php endfor; ?>


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

</body>
</html>