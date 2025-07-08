-- Database Schema Update Script
-- Run this to ensure all tables have the correct columns

-- Add created_at column to classes table if it doesn't exist


-- Add created_at column to subjects table if it doesn't exist
ALTER TABLE subjects 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add created_at column to users table if it doesn't exist
ALTER TABLE users
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add created_at column to teacher_subject_class table if it doesn't exist
ALTER TABLE teacher_subject_class
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add created_at column to student_class table if it doesn't exist
ALTER TABLE student_class
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add created_at column to quizzes table if it doesn't exist
ALTER TABLE quizzes
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add created_at column to assignments table if it doesn't exist
ALTER TABLE assignments
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add percentage column to quiz_submissions table if it doesn't exist
ALTER TABLE quiz_submissions
ADD COLUMN IF NOT EXISTS percentage DECIMAL(5,2) DEFAULT 0.00;

-- Add total_marks column to quizzes table if it doesn't exist
ALTER TABLE quizzes
ADD COLUMN IF NOT EXISTS total_marks INT DEFAULT 0;

-- Add is_active column to quizzes table if it doesn't exist
ALTER TABLE quizzes
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT 1;

-- Add time_limit column to quizzes table if it doesn't exist
ALTER TABLE quizzes
ADD COLUMN IF NOT EXISTS time_limit INT DEFAULT 30;

-- Add is_active column to assignments table if it doesn't exist
ALTER TABLE assignments
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT 1;

-- Add description column to quizzes table if it doesn't exist
ALTER TABLE quizzes
ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;

-- Add description column to assignments table if it doesn't exist
ALTER TABLE assignments
ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL;

-- Add score column to assignment_submissions table if it doesn't exist
ALTER TABLE assignment_submissions
ADD COLUMN IF NOT EXISTS score DECIMAL(5,2) DEFAULT NULL;

-- Add feedback column to assignment_submissions table if it doesn't exist
ALTER TABLE assignment_submissions
ADD COLUMN IF NOT EXISTS feedback TEXT DEFAULT NULL;

-- Ensure all foreign key constraints are properly set
-- (These will only run if the constraints don't already exist)

-- Update quiz_questions table structure if needed
ALTER TABLE quiz_questions 
MODIFY COLUMN question_type ENUM('multiple_choice', 'short_answer') NOT NULL DEFAULT 'multiple_choice';

-- Update quiz_questions to ensure options column exists and is JSON
ALTER TABLE quiz_questions 
MODIFY COLUMN options JSON DEFAULT NULL;

-- Ensure quiz_submissions has proper structure
ALTER TABLE quiz_submissions 
MODIFY COLUMN percentage DECIMAL(5,2) NOT NULL;

-- Ensure assignment_submissions has proper structure  
ALTER TABLE assignment_submissions 
MODIFY COLUMN score DECIMAL(5,2) DEFAULT NULL;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_student_class_student ON student_class(student_id);
CREATE INDEX IF NOT EXISTS idx_student_class_class ON student_class(class_id);
CREATE INDEX IF NOT EXISTS idx_teacher_subject_class_teacher ON teacher_subject_class(teacher_id);
CREATE INDEX IF NOT EXISTS idx_teacher_subject_class_subject ON teacher_subject_class(subject_id);
CREATE INDEX IF NOT EXISTS idx_teacher_subject_class_class ON teacher_subject_class(class_id);
CREATE INDEX IF NOT EXISTS idx_quiz_submissions_quiz ON quiz_submissions(quiz_id);
CREATE INDEX IF NOT EXISTS idx_quiz_submissions_student ON quiz_submissions(student_id);
CREATE INDEX IF NOT EXISTS idx_assignment_submissions_assignment ON assignment_submissions(assignment_id);
CREATE INDEX IF NOT EXISTS idx_assignment_submissions_student ON assignment_submissions(student_id);
CREATE INDEX IF NOT EXISTS idx_quiz_questions_quiz ON quiz_questions(quiz_id);

-- Verify table structures
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'classes' as table_name, COUNT(*) as record_count FROM classes
UNION ALL
SELECT 'subjects' as table_name, COUNT(*) as record_count FROM subjects
UNION ALL
SELECT 'teacher_subject_class' as table_name, COUNT(*) as record_count FROM teacher_subject_class
UNION ALL
SELECT 'student_class' as table_name, COUNT(*) as record_count FROM student_class
UNION ALL
SELECT 'quizzes' as table_name, COUNT(*) as record_count FROM quizzes
UNION ALL
SELECT 'quiz_questions' as table_name, COUNT(*) as record_count FROM quiz_questions
UNION ALL
SELECT 'quiz_submissions' as table_name, COUNT(*) as record_count FROM quiz_submissions
UNION ALL
SELECT 'assignments' as table_name, COUNT(*) as record_count FROM assignments
UNION ALL
SELECT 'assignment_submissions' as table_name, COUNT(*) as record_count FROM assignment_submissions;
