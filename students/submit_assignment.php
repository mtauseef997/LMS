<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$assignment_id = intval($_GET['id'] ?? 0);

if ($assignment_id <= 0) {
    header('Location: assignments.php');
    exit;
}

$assignment_query = "SELECT a.*, s.name as subject_name, c.name as class_name, u.name as teacher_name
                    FROM assignments a
                    JOIN subjects s ON a.subject_id = s.id
                    JOIN classes c ON a.class_id = c.id
                    JOIN users u ON a.teacher_id = u.id
                    JOIN student_class sc ON a.class_id = sc.class_id
                    WHERE a.id = ? AND sc.student_id = ?";

$assignment_stmt = $conn->prepare($assignment_query);
$assignment_stmt->bind_param("ii", $assignment_id, $student_id);
$assignment_stmt->execute();
$assignment_result = $assignment_stmt->get_result();
$assignment = $assignment_result->fetch_assoc();

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

$existing_submission_query = "SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
$existing_stmt = $conn->prepare($existing_submission_query);
$existing_stmt->bind_param("ii", $assignment_id, $student_id);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();
$existing_submission = $existing_result->fetch_assoc();

if ($existing_submission) {
    header('Location: view_assignment.php?id=' . $assignment_id);
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_text = trim($_POST['submission_text'] ?? '');
    $file_path = null;
    
    if (empty($submission_text) && empty($_FILES['submission_file']['name'])) {
        $message = 'Please provide either text submission or upload a file.';
        $messageType = 'error';
    } else {
        if (!empty($_FILES['submission_file']['name'])) {
            $upload_dir = '../assets/uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
            
            if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                $message = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG';
                $messageType = 'error';
            } else {
                $filename = $student_id . '_' . $assignment_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
                    $message = 'Failed to upload file.';
                    $messageType = 'error';
                    $file_path = null;
                }
            }
        }
        
        if ($messageType !== 'error') {
            $insert_query = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_path) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiss", $assignment_id, $student_id, $submission_text, $file_path);
            
            if ($insert_stmt->execute()) {
                header('Location: assignments.php?submitted=1');
                exit;
            } else {
                $message = 'Failed to submit assignment.';
                $messageType = 'error';
            }
        }
    }
}

$due_date = new DateTime($assignment['due_date']);
$now = new DateTime();
$is_overdue = $now > $due_date;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - <?php echo htmlspecialchars($assignment['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .assignment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .assignment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .submission-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .form-group textarea {
            width: 100%;
            min-height: 200px;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
        }
        .form-group input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
        }
        .overdue-warning {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #ef4444;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .back-btn {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        .back-btn:hover {
            background: #4b5563;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="assignment-container">
        <a href="assignments.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Assignments
        </a>

        <div class="assignment-header">
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($assignment['subject_name']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($assignment['class_name']); ?></p>
            <p><strong>Teacher:</strong> <?php echo htmlspecialchars($assignment['teacher_name']); ?></p>
            <p><strong>Due Date:</strong> <?php echo $due_date->format('M j, Y g:i A'); ?></p>
            <p><strong>Max Marks:</strong> <?php echo $assignment['max_marks']; ?></p>
        </div>

        <?php if ($is_overdue): ?>
        <div class="overdue-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Warning:</strong> This assignment is overdue. Late submissions may receive reduced marks.
        </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="submission-form">
            <h2 style="margin-bottom: 1rem; color: #333;">Assignment Description</h2>
            <p style="margin-bottom: 2rem; color: #555; line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
            </p>

            <h2 style="margin-bottom: 1rem; color: #333;">Submit Your Work</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="submission_text">Text Submission</label>
                    <textarea id="submission_text" name="submission_text" 
                              placeholder="Type your assignment submission here..."><?php echo htmlspecialchars($_POST['submission_text'] ?? ''); ?></textarea>
                    <small style="color: #666;">You can type your assignment directly here or upload a file below.</small>
                </div>

                <div class="form-group">
                    <label for="submission_file">File Upload (Optional)</label>
                    <input type="file" id="submission_file" name="submission_file" 
                           accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                    <small style="color: #666;">Allowed formats: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG (Max 10MB)</small>
                </div>

                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="submit-btn" 
                            onclick="return confirm('Are you sure you want to submit this assignment? You cannot modify it after submission.')">
                        <i class="fas fa-paper-plane"></i> Submit Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
