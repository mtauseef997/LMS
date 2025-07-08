<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Get filter values
$class_filter = $_GET['class'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get classes the student is enrolled in
$classes_query = "SELECT c.id, c.name 
                 FROM classes c
                 JOIN student_class sc ON c.id = sc.class_id
                 WHERE sc.student_id = ?
                 ORDER BY c.name";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $student_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

// Get subjects available to the student
$subjects_query = "SELECT DISTINCT s.id, s.name 
                  FROM subjects s
                  JOIN teacher_subject_class tsc ON s.id = tsc.subject_id
                  JOIN student_class sc ON tsc.class_id = sc.class_id
                  WHERE sc.student_id = ?
                  ORDER BY s.name";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $student_id);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();
$subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);

// Build the quizzes query with filters
$quizzes_query = "SELECT q.id, q.title, q.description, q.time_limit, q.total_marks,
                 s.name as subject_name, c.name as class_name, u.name as teacher_name,
                 CASE WHEN qs.id IS NOT NULL THEN 'Completed' ELSE 'Available' END as status,
                 qs.score, qs.percentage, qs.submitted_at
                 FROM quizzes q
                 JOIN subjects s ON q.subject_id = s.id
                 JOIN classes c ON q.class_id = c.id
                 JOIN users u ON q.teacher_id = u.id
                 JOIN student_class sc ON q.class_id = sc.class_id
                 LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = ?
                 WHERE sc.student_id = ? AND q.is_active = 1";

$params = [$student_id, $student_id];
$types = "ii";

if (!empty($class_filter)) {
    $quizzes_query .= " AND q.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

if (!empty($subject_filter)) {
    $quizzes_query .= " AND q.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";