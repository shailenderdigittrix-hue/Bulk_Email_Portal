<?php

    session_start();

    require 'config/database.php';

    $message = "";
    if (isset($_POST['upload'])) {
        try {

            // Check file selected
            if (empty($_FILES['csv']['name'])) {

                $_SESSION['error'] = "Please select a CSV file";
                header("Location: index.php");
                exit;
            }

            // Open CSV
            $file = fopen($_FILES['csv']['tmp_name'], 'r');

            // Skip Header
            fgetcsv($file);

            $inserted = 0;

            while (($row = fgetcsv($file)) !== false) {

                $name = trim($row[0]);
                $email = trim($row[1]);
                $company = trim($row[2]);

                // Validate Email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                // Check Duplicate
                $check = $pdo->prepare("
                    SELECT id
                    FROM contacts
                    WHERE email = ?
                ");

                $check->execute([$email]);

                if ($check->rowCount() == 0) {

                    $insert = $pdo->prepare("
                        INSERT INTO contacts(
                            name,
                            email,
                            company
                        ) VALUES(?,?,?)
                    ");

                    $insert->execute([
                        $name,
                        $email,
                        $company
                    ]);

                    $inserted++;
                }
            }

            fclose($file);

            $_SESSION['success'] =
                $inserted . " contacts imported successfully";

        } catch (Exception $e) {

            $_SESSION['error'] =
                "Import failed : " . $e->getMessage();
        }

        // Redirect
        header("Location: index.php");
        exit;
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Contacts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">

    <h2>Upload Contacts CSV</h2>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="csv" class="form-control mb-3" required>
        <button type="submit" name="upload" class="btn btn-primary">Upload</button>
    </form>

    <div class="mt-4">
        <h5>CSV Format</h5>
        <pre>
            name,email,company
            John,john@test.com,ABC Ltd
        </pre>
    </div>

</div>
</body>
</html>