# 📘 Learning Management System (LMS)

A role-based Learning Management System (LMS) built with PHP, MySQL, Bootstrap, and JavaScript to help schools and institutions manage classes, users, quizzes, assignments, results, and attendance through a centralized dashboard.

---

## 🚀 Features

### 🔐 Authentication
- Secure login/logout system
- Role-based dashboards: Admin, Teacher, Student

### 👩‍🏫 Admin Panel
- Manage Users (Admins, Teachers, Students)
- Manage Classes & Subjects
- Assign Class Teachers
- Control permissions
- View reports (students, grades, attendance)

### 👨‍🏫 Teacher Panel
- Manage subjects and students
- Create and assign quizzes and assignments
- Enter and update student marks
- View and track attendance
- Add feedback and grades

### 👨‍🎓 Student Panel
- View assigned subjects and classes
- Submit assignments and quizzes
- View grades, feedback, and attendance
- Access learning materials
---

## 🗂️ Folder Structure
lms/
├── admin/ # Admin pages
├── teacher/ # Teacher dashboard and tools
├── student/ # Student dashboard and views
├── assets/ # CSS, JS, images
│ ├── css/
│ ├── js/
├── includes/ # Shared functions and DB connection
├── database/ # SQL schema
├── dashboard.php # Role-based redirect
├── index.php # Login page
└── logout.php

 🛠️ Technologies Used

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP 8+
- **Database**: MySQL
- **Extras**: jQuery, DataTables, Chart.js (optional)

---
