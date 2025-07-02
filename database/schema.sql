-- ========================================
-- LMS DATABASE SCHEMA
-- ========================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS lms_db;
USE lms_db;

-- ------------------------
-- USERS TABLE
-- ------------------------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'teacher', 'student') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------
-- CLASSES TABLE
-- ------------------------
CREATE TABLE IF NOT EXISTS classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL, -- e.g., "Grade 6A"
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------
-- SUBJECTS TABLE
-- ------------------------
CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL, -- e.g., "Math"
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------
-- LINK: TEACHER - SUBJECT - CLASS
-- ------------------------
CREATE TABLE IF NOT EXISTS teacher_subject_class (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT NOT NULL,
  subject_id INT NOT NULL,
  class_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  UNIQUE KEY unique_teacher_subject_class (teacher_id, subject_id, class_id)
);

-- ------------------------
-- LINK: STUDENT - CLASS
-- ------------------------
CREATE TABLE IF NOT EXISTS student_class (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  UNIQUE KEY unique_student_class (student_id, class_id)
);

-- ------------------------
-- QUIZZES
-- ------------------------
CREATE TABLE IF NOT EXISTS quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  subject_id INT NOT NULL,
  class_id INT NOT NULL,
  teacher_id INT NOT NULL,
  time_limit INT DEFAULT 30, -- in minutes
  total_marks INT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------
-- QUIZ QUESTIONS
-- ------------------------
CREATE TABLE IF NOT EXISTS quiz_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  question TEXT NOT NULL,
  option_a VARCHAR(255),
  option_b VARCHAR(255),
  option_c VARCHAR(255),
  option_d VARCHAR(255),
  correct_option CHAR(1), -- 'A', 'B', 'C', 'D'
  marks INT DEFAULT 1,
  question_order INT DEFAULT 0,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ------------------------
-- QUIZ SUBMISSIONS
-- ------------------------
CREATE TABLE IF NOT EXISTS quiz_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  student_id INT NOT NULL,
  score INT DEFAULT 0, -- Total score achieved
  total_marks INT DEFAULT 0, -- Total possible marks
  percentage DECIMAL(5,2) DEFAULT 0.00,
  time_taken INT DEFAULT 0, -- in minutes
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_quiz_student (quiz_id, student_id)
);

-- ------------------------
-- QUIZ ANSWERS (Student responses)
-- ------------------------
CREATE TABLE IF NOT EXISTS quiz_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  submission_id INT NOT NULL,
  question_id INT NOT NULL,
  selected_option CHAR(1), -- 'A', 'B', 'C', 'D'
  is_correct BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (submission_id) REFERENCES quiz_submissions(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- ------------------------
-- ASSIGNMENTS
-- ------------------------
CREATE TABLE IF NOT EXISTS assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  subject_id INT NOT NULL,
  class_id INT NOT NULL,
  teacher_id INT NOT NULL,
  due_date DATE,
  max_marks INT DEFAULT 100,
  is_active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------
-- ASSIGNMENT SUBMISSIONS
-- ------------------------
CREATE TABLE IF NOT EXISTS assignment_submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT NOT NULL,
  student_id INT NOT NULL,
  file_path VARCHAR(255), -- path to uploaded file
  submission_text TEXT, -- for text submissions
  grade INT DEFAULT NULL, -- 0-100 or NULL if not graded
  feedback TEXT,
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  graded_at DATETIME DEFAULT NULL,
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_assignment_student (assignment_id, student_id)
);

-- ------------------------
-- ANNOUNCEMENTS
-- ------------------------
CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  author_id INT NOT NULL,
  class_id INT DEFAULT NULL, -- NULL means for all classes
  subject_id INT DEFAULT NULL, -- NULL means for all subjects
  is_active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- ------------------------
-- INSERT SAMPLE DATA
-- ------------------------

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@lms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample classes
INSERT IGNORE INTO classes (name) VALUES 
('Grade 6A'), ('Grade 6B'), ('Grade 7A'), ('Grade 7B'), ('Grade 8A'), ('Grade 8B');

-- Insert sample subjects
INSERT IGNORE INTO subjects (name) VALUES 
('Mathematics'), ('English'), ('Science'), ('History'), ('Geography'), ('Computer Science');
