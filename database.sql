-- Create the main database
CREATE DATABASE limkokwing_ars;
USE limkokwing_ars;

-- Users table for storing all system users (admin, lecturers, PRLs)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(20) UNIQUE,  -- Must be unique across all users
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,          -- Must be unique across all users
    password VARCHAR(50),
    role ENUM('admin', 'lecturer', 'prl'),  -- Restrict roles to these three types
    status BOOLEAN DEFAULT 1,           -- Account active/inactive flag
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Academic years configuration
CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year_name VARCHAR(20),
    is_current BOOLEAN DEFAULT 0,       -- Flag for current academic year
    status BOOLEAN DEFAULT 1            -- Active/inactive flag
);

-- Semesters within academic years
CREATE TABLE semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    semester_name VARCHAR(50),
    academic_year_id INT,
    start_date DATE,
    end_date DATE,
    is_current BOOLEAN DEFAULT 0,       -- Flag for current semester
    status BOOLEAN DEFAULT 1,           -- Active/inactive flag
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

-- Academic modules/subjects
CREATE TABLE modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_code VARCHAR(20),
    module_name VARCHAR(100),
    status BOOLEAN DEFAULT 1            -- Active/inactive flag
);

-- Student classes/groups
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50),
    status BOOLEAN DEFAULT 1            -- Active/inactive flag
);

-- Student information
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(9) UNIQUE,   -- Must be unique across all students
    full_name VARCHAR(100),
    contact VARCHAR(20),
    class_id INT,
    status BOOLEAN DEFAULT 1,           -- Active/inactive flag
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Module assignments to lecturers
CREATE TABLE lecturer_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lecturer_id INT,
    module_id INT,
    class_id INT,
    semester_id INT,
    academic_year_id INT,
    status BOOLEAN DEFAULT 1,           -- Active/inactive flag
    FOREIGN KEY (lecturer_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES modules(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

-- Weekly teaching reports
CREATE TABLE weekly_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lecturer_id INT,
    module_id INT,
    class_id INT,
    chapter_covered VARCHAR(200),
    learning_outcomes TEXT,
    mode_of_delivery VARCHAR(50),
    student_attendance INT,
    challenges TEXT,
    recommendations TEXT,
    malpractice_instances TEXT,
    report_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES modules(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- Student attendance tracking
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    module_id INT,
    class_id INT,
    lecturer_id INT,
    status ENUM('present', 'absent'),   -- Only allow present/absent values
    date DATE,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (module_id) REFERENCES modules(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (lecturer_id) REFERENCES users(id)
);

-- System activity logs for audit trail
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);