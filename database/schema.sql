-- ========================================
-- LMS (Learning Management System) Database Schema
-- Complete Fresh Schema - Version 2.0
-- ========================================

-- Create database
CREATE DATABASE IF NOT EXISTS lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lms;

-- ========================================
-- USERS TABLE
-- ========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- ========================================
-- CLASSES TABLE
-- ========================================
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    grade_level VARCHAR(50),
    academic_year VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_grade (grade_level),
    INDEX idx_active (is_active)
);

-- ========================================
-- SUBJECTS TABLE
-- ========================================
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,
    description TEXT,
    credits INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_code (code),
    INDEX idx_active (is_active)
);

-- ========================================
-- TEACHER-SUBJECT-CLASS ASSIGNMENTS
-- ========================================
CREATE TABLE teacher_subject_class (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    academic_year VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject_class (teacher_id, subject_id, class_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_subject (subject_id),
    INDEX idx_class (class_id)
);

-- ========================================
-- STUDENT-CLASS ASSIGNMENTS
-- ========================================
CREATE TABLE student_class (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    enrollment_date DATE,
    academic_year VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_class (student_id, class_id),
    INDEX idx_student (student_id),
    INDEX idx_class (class_id)
);

-- ========================================
-- QUIZZES TABLE
-- ========================================
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    time_limit INT DEFAULT 60,
    max_marks INT DEFAULT 0,
    total_marks INT DEFAULT 0,
    passing_marks INT DEFAULT 0,
    instructions TEXT,
    start_date DATETIME,
    end_date DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    allow_retake BOOLEAN DEFAULT FALSE,
    show_results BOOLEAN DEFAULT TRUE,
    randomize_questions BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_subject (subject_id),
    INDEX idx_class (class_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_active (is_active),
    INDEX idx_dates (start_date, end_date)
);

-- ========================================
-- QUIZ QUESTIONS TABLE
-- ========================================
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'short_answer', 'essay', 'true_false') DEFAULT 'multiple_choice',
    options TEXT,
    correct_answer TEXT NOT NULL,
    explanation TEXT,
    marks INT DEFAULT 1,
    question_order INT DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_order (question_order),
    INDEX idx_type (question_type)
);

-- ========================================
-- QUIZ SUBMISSIONS TABLE
-- ========================================
CREATE TABLE quiz_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    answers LONGTEXT,
    score INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    time_taken INT DEFAULT 0,
    status ENUM('submitted', 'graded', 'in_progress') DEFAULT 'submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_quiz_student (quiz_id, student_id),
    INDEX idx_quiz (quiz_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_submitted (submitted_at)
);

-- ========================================
-- ASSIGNMENTS TABLE
-- ========================================
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    due_date DATETIME,
    max_marks INT DEFAULT 100,
    instructions TEXT,
    file_upload_allowed BOOLEAN DEFAULT TRUE,
    max_file_size INT DEFAULT 10485760,
    allowed_file_types VARCHAR(255) DEFAULT 'pdf,doc,docx,txt',
    is_active BOOLEAN DEFAULT TRUE,
    late_submission_allowed BOOLEAN DEFAULT FALSE,
    late_penalty_percent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_subject (subject_id),
    INDEX idx_class (class_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_due_date (due_date),
    INDEX idx_active (is_active)
);

-- ========================================
-- ASSIGNMENT SUBMISSIONS TABLE
-- ========================================
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text LONGTEXT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    score DECIMAL(5,2) DEFAULT NULL,
    feedback LONGTEXT,
    status ENUM('submitted', 'graded', 'late', 'draft') DEFAULT 'submitted',
    is_late BOOLEAN DEFAULT FALSE,
    graded_at TIMESTAMP NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment_student (assignment_id, student_id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_submitted (submitted_at)
);

-- ========================================
-- ATTENDANCE TABLE
-- ========================================
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    notes TEXT,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date_subject (student_id, attendance_date, subject_id),
    INDEX idx_student (student_id),
    INDEX idx_class (class_id),
    INDEX idx_subject (subject_id),
    INDEX idx_date (attendance_date),
    INDEX idx_status (status)
);

-- ========================================
-- GRADES TABLE
-- ========================================
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    grade_type ENUM('quiz', 'assignment', 'exam', 'project', 'participation') NOT NULL,
    reference_id INT,
    grade_value DECIMAL(5,2) NOT NULL,
    max_grade DECIMAL(5,2) NOT NULL,
    percentage DECIMAL(5,2) GENERATED ALWAYS AS ((grade_value / max_grade) * 100) STORED,
    grade_date DATE NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_subject (subject_id),
    INDEX idx_class (class_id),
    INDEX idx_type (grade_type),
    INDEX idx_date (grade_date)
);

-- ========================================
-- ANNOUNCEMENTS TABLE
-- ========================================
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    target_audience ENUM('all', 'students', 'teachers', 'class_specific') DEFAULT 'all',
    class_id INT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
    publish_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expire_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_author (author_id),
    INDEX idx_audience (target_audience),
    INDEX idx_class (class_id),
    INDEX idx_priority (priority),
    INDEX idx_active (is_active),
    INDEX idx_publish (publish_date)
);

-- ========================================
-- SAMPLE DATA INSERTION
-- ========================================

-- Insert default admin user
INSERT INTO users (name, email, password, role) VALUES
('System Administrator', 'admin@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample classes
INSERT INTO classes (name, description, grade_level, academic_year) VALUES
('Class 10A', 'Grade 10 Section A', '10', '2024-2025'),
('Class 10B', 'Grade 10 Section B', '10', '2024-2025'),
('Class 11A', 'Grade 11 Section A', '11', '2024-2025'),
('Class 12A', 'Grade 12 Section A', '12', '2024-2025');

-- Insert sample subjects
INSERT INTO subjects (name, code, description, credits) VALUES
('Mathematics', 'MATH101', 'Basic Mathematics', 4),
('Physics', 'PHY101', 'Basic Physics', 4),
('Chemistry', 'CHEM101', 'Basic Chemistry', 4),
('Biology', 'BIO101', 'Basic Biology', 4),
('English', 'ENG101', 'English Language and Literature', 3),
('History', 'HIST101', 'World History', 3),
('Computer Science', 'CS101', 'Introduction to Computer Science', 3);

-- ========================================
-- INDEXES FOR PERFORMANCE
-- ========================================

-- Additional composite indexes for common queries
CREATE INDEX idx_quiz_class_subject ON quizzes(class_id, subject_id);
CREATE INDEX idx_assignment_class_subject ON assignments(class_id, subject_id);
CREATE INDEX idx_submission_student_quiz ON quiz_submissions(student_id, quiz_id);
CREATE INDEX idx_submission_student_assignment ON assignment_submissions(student_id, assignment_id);
CREATE INDEX idx_attendance_student_date ON attendance(student_id, attendance_date);
CREATE INDEX idx_grades_student_subject ON grades(student_id, subject_id);

-- ========================================
-- VIEWS FOR COMMON QUERIES
-- ========================================

-- Student dashboard view
CREATE VIEW student_dashboard AS
SELECT
    u.id as student_id,
    u.name as student_name,
    c.name as class_name,
    s.name as subject_name,
    COUNT(DISTINCT q.id) as total_quizzes,
    COUNT(DISTINCT qs.id) as completed_quizzes,
    COUNT(DISTINCT a.id) as total_assignments,
    COUNT(DISTINCT asub.id) as submitted_assignments,
    AVG(qs.percentage) as avg_quiz_score,
    AVG(asub.score) as avg_assignment_score
FROM users u
JOIN student_class sc ON u.id = sc.student_id
JOIN classes c ON sc.class_id = c.id
JOIN teacher_subject_class tsc ON c.id = tsc.class_id
JOIN subjects s ON tsc.subject_id = s.id
LEFT JOIN quizzes q ON q.class_id = c.id AND q.subject_id = s.id
LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = u.id
LEFT JOIN assignments a ON a.class_id = c.id AND a.subject_id = s.id
LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = u.id
WHERE u.role = 'student' AND u.is_active = TRUE
GROUP BY u.id, c.id, s.id;

-- Teacher dashboard view
CREATE VIEW teacher_dashboard AS
SELECT
    u.id as teacher_id,
    u.name as teacher_name,
    c.name as class_name,
    s.name as subject_name,
    COUNT(DISTINCT sc.student_id) as total_students,
    COUNT(DISTINCT q.id) as total_quizzes,
    COUNT(DISTINCT a.id) as total_assignments,
    COUNT(DISTINCT qs.id) as quiz_submissions,
    COUNT(DISTINCT asub.id) as assignment_submissions
FROM users u
JOIN teacher_subject_class tsc ON u.id = tsc.teacher_id
JOIN classes c ON tsc.class_id = c.id
JOIN subjects s ON tsc.subject_id = s.id
LEFT JOIN student_class sc ON c.id = sc.class_id
LEFT JOIN quizzes q ON q.class_id = c.id AND q.subject_id = s.id AND q.teacher_id = u.id
LEFT JOIN assignments a ON a.class_id = c.id AND a.subject_id = s.id AND a.teacher_id = u.id
LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id
LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id
WHERE u.role = 'teacher' AND u.is_active = TRUE
GROUP BY u.id, c.id, s.id;

-- ========================================
-- TRIGGERS FOR DATA INTEGRITY
-- ========================================

-- Update quiz total marks when questions are added/updated
DELIMITER //
CREATE TRIGGER update_quiz_total_marks
AFTER INSERT ON quiz_questions
FOR EACH ROW
BEGIN
    UPDATE quizzes
    SET total_marks = (
        SELECT COALESCE(SUM(marks), 0)
        FROM quiz_questions
        WHERE quiz_id = NEW.quiz_id
    )
    WHERE id = NEW.quiz_id;
END//

CREATE TRIGGER update_quiz_total_marks_on_update
AFTER UPDATE ON quiz_questions
FOR EACH ROW
BEGIN
    UPDATE quizzes
    SET total_marks = (
        SELECT COALESCE(SUM(marks), 0)
        FROM quiz_questions
        WHERE quiz_id = NEW.quiz_id
    )
    WHERE id = NEW.quiz_id;
END//

CREATE TRIGGER update_quiz_total_marks_on_delete
AFTER DELETE ON quiz_questions
FOR EACH ROW
BEGIN
    UPDATE quizzes
    SET total_marks = (
        SELECT COALESCE(SUM(marks), 0)
        FROM quiz_questions
        WHERE quiz_id = OLD.quiz_id
    )
    WHERE id = OLD.quiz_id;
END//
DELIMITER ;

-- ========================================
-- SCHEMA COMPLETE
-- ========================================