<?php
session_start();
require_once '../config/db.php';

// Redirect non-logged in users
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Handle subject deletion
if (isset($_GET['delete'])) {
    $subject_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    if ($stmt->execute()) {
        $success_message = "Subject deleted successfully.";
    } else {
        $error_message = "Failed to delete subject.";
    }
    header("Location: subjects.php");
    exit;
}

// Handle form submission for adding/editing subjects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error_message = "Subject name is required.";
    } else {
        if (isset($_POST['subject_id'])) {
            // Update existing subject
            $subject_id = intval($_POST['subject_id']);
            $stmt = $conn->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $subject_id);
            if ($stmt->execute()) {
                $success_message = "Subject updated successfully.";
            } else {
                $error_message = "Failed to update subject.";
            }
        } else {
            // Add new subject
            $stmt = $conn->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $success_message = "Subject added successfully.";
            } else {
                $error_message = "Failed to add subject.";
            }
        }
    }
}

// Fetch subject for editing if edit parameter is set
$edit_subject = null;
if (isset($_GET['edit'])) {
    $subject_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $edit_subject = $stmt->get_result()->fetch_assoc();
}

// Fetch all subjects
$query = "SELECT * FROM subjects ORDER BY id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Subjects - EduLearn LMS</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include('sidebar.php'); ?>

        <main class="main-content">
            <header class="content-header">
                <h1>Subjects</h1>
                <p>List of all subjects in the system</p>
            </header>

            <div class="content-card">
                <div class="card-header">
                    <h3>Subjects</h3>
                </div>
                <div class="card-content">
                    <?php if ($result->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id']; ?></td>
                                <td><?= htmlspecialchars($row['name']); ?></td>
                                <td><?= htmlspecialchars($row['description']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>No subjects found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>