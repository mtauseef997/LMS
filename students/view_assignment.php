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

$submission_query = "SELECT asub.*, a.title, a.description, a.max_marks, a.due_date,
                    s.name as subject_name, c.name as class_name, u.name as teacher_name
                    FROM assignment_submissions asub
                    JOIN assignments a ON asub.assignment_id = a.id
                    JOIN subjects s ON a.subject_id = s.id
                    JOIN classes c ON a.class_id = c.id
                    JOIN users u ON a.teacher_id = u.id
                    WHERE asub.assignment_id = ? AND asub.student_id = ?";

$submission_stmt = $conn->prepare($submission_query);
$submission_stmt->bind_param("ii", $assignment_id, $student_id);
$submission_stmt->execute();
$submission_result = $submission_stmt->get_result();
$submission = $submission_result->fetch_assoc();

if (!$submission) {
    header('Location: assignments.php');
    exit;
}

$due_date = new DateTime($submission['due_date']);
$submitted_date = new DateTime($submission['submitted_at']);
$is_late = $submitted_date > $due_date;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Submission - <?php echo htmlspecialchars($submission['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .submission-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        .submission-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 1rem;
        }
        .status-submitted { background: #dcfce7; color: #166534; }
        .status-graded { background: #dbeafe; color: #1e40af; }
        .status-late { background: #fef3c7; color: #92400e; }
        .submission-details {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .detail-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            color: #6b7280;
        }
        .submission-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .grade-display {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .grade-score {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .feedback-section {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
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
        .file-download {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 1rem;
        }
        .file-download:hover {
            background: #5a67d8;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="submission-container">
        <a href="assignments.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Assignments
        </a>

        <div class="submission-header">
            <h1><?php echo htmlspecialchars($submission['title']); ?></h1>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($submission['subject_name']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($submission['class_name']); ?></p>
            <p><strong>Teacher:</strong> <?php echo htmlspecialchars($submission['teacher_name']); ?></p>
            
            <div>
                <span class="status-badge status-submitted">
                    <i class="fas fa-check-circle"></i> Submitted
                </span>
                <?php if ($submission['score'] !== null): ?>
                <span class="status-badge status-graded">
                    <i class="fas fa-star"></i> Graded
                </span>
                <?php endif; ?>
                <?php if ($is_late): ?>
                <span class="status-badge status-late">
                    <i class="fas fa-clock"></i> Late Submission
                </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($submission['score'] !== null): ?>
        <div class="grade-display">
            <div class="grade-score"><?php echo $submission['score']; ?>/<?php echo $submission['max_marks']; ?></div>
            <p>Your Grade: <?php echo number_format(($submission['score'] / $submission['max_marks']) * 100, 1); ?>%</p>
            <?php if ($submission['graded_at']): ?>
            <p style="font-size: 0.9rem; opacity: 0.9;">
                Graded on <?php echo date('M j, Y g:i A', strtotime($submission['graded_at'])); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="submission-details">
            <h2 style="margin-bottom: 1rem; color: #333;">Submission Details</h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Due Date</div>
                    <div class="detail-value"><?php echo $due_date->format('M j, Y g:i A'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Submitted</div>
                    <div class="detail-value">
                        <?php echo $submitted_date->format('M j, Y g:i A'); ?>
                        <?php if ($is_late): ?>
                        <br><span style="color: #ef4444; font-weight: 500;">Late Submission</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Max Marks</div>
                    <div class="detail-value"><?php echo $submission['max_marks']; ?></div>
                </div>
                <?php if ($submission['score'] !== null): ?>
                <div class="detail-item">
                    <div class="detail-label">Your Score</div>
                    <div class="detail-value" style="font-weight: 600; color: #059669;">
                        <?php echo $submission['score']; ?>/<?php echo $submission['max_marks']; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="submission-content">
            <h2 style="margin-bottom: 1rem; color: #333;">Assignment Description</h2>
            <p style="color: #555; line-height: 1.6; margin-bottom: 2rem;">
                <?php echo nl2br(htmlspecialchars($submission['description'])); ?>
            </p>

            <h2 style="margin-bottom: 1rem; color: #333;">Your Submission</h2>
            
            <?php if (!empty($submission['submission_text'])): ?>
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                <h4 style="margin-bottom: 1rem; color: #374151;">Text Submission:</h4>
                <div style="white-space: pre-wrap; line-height: 1.6; color: #4b5563;">
                    <?php echo htmlspecialchars($submission['submission_text']); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($submission['file_path']) && file_exists($submission['file_path'])): ?>
            <div style="margin-top: 1rem;">
                <h4 style="margin-bottom: 0.5rem; color: #374151;">Uploaded File:</h4>
                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                   class="file-download" target="_blank">
                    <i class="fas fa-download"></i>
                    Download Submitted File
                </a>
            </div>
            <?php endif; ?>

            <?php if (!empty($submission['feedback'])): ?>
            <div class="feedback-section">
                <h4 style="margin-bottom: 1rem; color: #0369a1;">
                    <i class="fas fa-comment"></i> Teacher Feedback
                </h4>
                <p style="margin: 0; line-height: 1.6; color: #374151;">
                    <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($submission['score'] === null): ?>
        <div style="text-align: center; padding: 2rem; background: #fef3c7; border-radius: 12px; color: #92400e;">
            <i class="fas fa-hourglass-half" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">Awaiting Grade</h3>
            <p style="margin: 0;">Your assignment has been submitted and is waiting to be graded by your teacher.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
