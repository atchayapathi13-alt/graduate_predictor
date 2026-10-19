-- ==========================================================
-- 🎓 Graduate Predicting Analysis System
-- Database Schema & Comprehensive Seed Data
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `graduate_predictor` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `graduate_predictor`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Departments Table
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `recommendations`;
DROP TABLE IF EXISTS `predictions`;
DROP TABLE IF EXISTS `performance_analysis`;
DROP TABLE IF EXISTS `extracurricular_activities`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `internships`;
DROP TABLE IF EXISTS `certifications`;
DROP TABLE IF EXISTS `student_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `academic_records`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dept_code` VARCHAR(20) NOT NULL UNIQUE,
    `dept_name` VARCHAR(150) NOT NULL,
    `hod_name` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'faculty', 'admin') NOT NULL DEFAULT 'student',
    `phone` VARCHAR(20) DEFAULT NULL,
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Students Table
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `student_id` VARCHAR(50) NOT NULL UNIQUE,
    `dob` DATE DEFAULT NULL,
    `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    `department_id` INT DEFAULT NULL,
    `degree` VARCHAR(50) DEFAULT 'B.Tech / B.E',
    `academic_year` VARCHAR(20) DEFAULT '2023-2027',
    `graduation_year` INT DEFAULT 2027,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    INDEX `idx_student_code` (`student_id`),
    INDEX `idx_dept` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Faculty Table
CREATE TABLE `faculty` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `faculty_id` VARCHAR(50) NOT NULL UNIQUE,
    `department_id` INT DEFAULT NULL,
    `designation` VARCHAR(100) DEFAULT 'Assistant Professor',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Academic Records Table
CREATE TABLE `academic_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL UNIQUE,
    `tenth_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `twelfth_percentage` DECIMAL(5,2) DEFAULT NULL,
    `diploma_percentage` DECIMAL(5,2) DEFAULT NULL,
    `current_cgpa` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `current_semester` INT NOT NULL DEFAULT 1,
    `num_backlogs` INT NOT NULL DEFAULT 0,
    `cleared_backlogs` INT NOT NULL DEFAULT 0,
    `attendance_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `sem1_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem2_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem3_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem4_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem5_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem6_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem7_gpa` DECIMAL(4,2) DEFAULT NULL,
    `sem8_gpa` DECIMAL(4,2) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Skills Definition Table
CREATE TABLE `skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `category` ENUM('technical', 'programming', 'soft_skill') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Student Skills Ratings Table
CREATE TABLE `student_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `skill_id` INT NOT NULL,
    `rating` INT NOT NULL DEFAULT 1, -- 1 to 5
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_skill` (`student_id`, `skill_id`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`skill_id`) REFERENCES `skills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Certifications Table
CREATE TABLE `certifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `provider` VARCHAR(100) NOT NULL,
    `issue_date` DATE DEFAULT NULL,
    `certification_level` ENUM('Beginner', 'Intermediate', 'Advanced', 'Professional') DEFAULT 'Intermediate',
    `credential_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Internships Table
CREATE TABLE `internships` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `has_internship` TINYINT(1) DEFAULT 1,
    `company` VARCHAR(150) NOT NULL,
    `duration_months` INT DEFAULT 1,
    `domain` VARCHAR(100) NOT NULL,
    `internship_type` ENUM('Full-time', 'Part-time', 'Virtual', 'Research') DEFAULT 'Full-time',
    `skills_gained` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Projects Table
CREATE TABLE `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `domain` VARCHAR(100) NOT NULL,
    `technologies` VARCHAR(255) NOT NULL,
    `complexity` ENUM('Basic', 'Intermediate', 'Advanced') DEFAULT 'Intermediate',
    `status` ENUM('Ongoing', 'Completed') DEFAULT 'Completed',
    `project_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Extracurricular Activities Table
CREATE TABLE `extracurricular_activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `activity_type` ENUM('Hackathons', 'Coding competitions', 'Project exhibitions', 'Sports', 'Clubs', 'Volunteering', 'Paper presentations', 'Workshops') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `achievement_level` VARCHAR(100) DEFAULT 'Participant',
    `event_date` DATE DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Performance Analysis Table
CREATE TABLE `performance_analysis` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL UNIQUE,
    `academic_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `technical_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `career_readiness_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `soft_skills_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `extracurricular_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `overall_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Predictions Table
CREATE TABLE `predictions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL UNIQUE,
    `category` ENUM('High Readiness', 'Moderate Readiness', 'Needs Improvement') NOT NULL,
    `confidence_score` DECIMAL(5,2) NOT NULL DEFAULT 85.00,
    `prediction_notes` TEXT DEFAULT NULL,
    `predicted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Recommendations Table
CREATE TABLE `recommendations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('High', 'Medium', 'Low') DEFAULT 'Medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Settings Table
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Reports Audit Table
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `generated_by` INT NOT NULL,
    `report_type` VARCHAR(100) DEFAULT 'Student Full Analysis',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- DEFAULT SETTINGS
-- ==========================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('academic_weight', '40', 'Academic Performance Weight Percentage (Default 40%)'),
('technical_weight', '25', 'Technical Skills Weight Percentage (Default 25%)'),
('career_weight', '20', 'Career Readiness Weight Percentage (Default 20%)'),
('soft_skills_weight', '10', 'Soft Skills Weight Percentage (Default 10%)'),
('extracurricular_weight', '5', 'Extracurricular Activities Weight Percentage (Default 5%)'),
('min_attendance_threshold', '75', 'Minimum recommended attendance percentage threshold'),
('min_cgpa_threshold', '7.0', 'Benchmark minimum CGPA for placement readiness'),
('high_readiness_threshold', '75', 'Threshold overall score for High Readiness outcome'),
('moderate_readiness_threshold', '50', 'Threshold overall score for Moderate Readiness outcome'),
('system_name', 'Graduate Predicting Analysis System', 'Application Brand Title');

-- ==========================================================
-- SKILLS DEFINITION SEED DATA
-- ==========================================================
INSERT INTO `skills` (`name`, `category`) VALUES
-- Technical & Programming
('Java', 'programming'),
('Python', 'programming'),
('C', 'programming'),
('C++', 'programming'),
('JavaScript', 'programming'),
('SQL', 'programming'),
('Data Structures', 'technical'),
('Database Management', 'technical'),
('Cloud Computing', 'technical'),
('Cybersecurity', 'technical'),
('Data Analytics', 'technical'),
('Machine Learning', 'technical'),
('Web Development', 'technical'),
('Programming Fundamentals', 'technical'),
-- Soft Skills
('Communication', 'soft_skill'),
('Teamwork', 'soft_skill'),
('Leadership', 'soft_skill'),
('Problem Solving', 'soft_skill'),
('Time Management', 'soft_skill'),
('Adaptability', 'soft_skill'),
('Presentation Skills', 'soft_skill');

-- ==========================================================
-- DEPARTMENTS SEED DATA
-- ==========================================================
INSERT INTO `departments` (`dept_code`, `dept_name`, `hod_name`) VALUES
('CSE', 'Computer Science and Engineering', 'Dr. Ramesh Kumar'),
('IT', 'Information Technology', 'Dr. S. Meenakshi'),
('ECE', 'Electronics and Communication Engineering', 'Dr. K. Venkatesh'),
('AIDS', 'Artificial Intelligence and Data Science', 'Dr. Priya Sundaram'),
('MECH', 'Mechanical Engineering', 'Dr. Anand Verma');

-- ==========================================================
-- USERS SEED DATA
-- Default Password for all accounts: Password@123
-- BCrypt Hash: $2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm
-- ==========================================================

-- Admin User
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`) VALUES
(1, 'System Administrator', 'admin@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'admin', '+91 9876543210');

-- Faculty Users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`) VALUES
(2, 'Dr. Ramesh Kumar', 'faculty.cse@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'faculty', '+91 9876543211'),
(3, 'Dr. S. Meenakshi', 'faculty.it@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'faculty', '+91 9876543212');

INSERT INTO `faculty` (`user_id`, `faculty_id`, `department_id`, `designation`) VALUES
(2, 'FAC2024001', 1, 'Professor & Head of Department'),
(3, 'FAC2024002', 2, 'Associate Professor');

-- Student Users (10 Students)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`) VALUES
(4, 'Aarav Sharma', 'student1@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456781'),
(5, 'Priya Patel', 'student2@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456782'),
(6, 'Rohan Verma', 'student3@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456783'),
(7, 'Sneha Kulkarni', 'student4@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456784'),
(8, 'Vikram Singh', 'student5@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456785'),
(9, 'Ananya Nair', 'student6@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456786'),
(10, 'Rahul Gupta', 'student7@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456787'),
(11, 'Divya Menon', 'student8@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456788'),
(12, 'Karthik Raja', 'student9@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456789'),
(13, 'Tanvi Joshi', 'student10@graduate.edu', '$2y$10$FrNZ8Erz2eFip7QFFLHFOOtyyquZCRaDdoPzJ7LkvaOssNMazmZhm', 'student', '+91 9123456790');

-- 10 Students Details
INSERT INTO `students` (`id`, `user_id`, `student_id`, `dob`, `gender`, `department_id`, `degree`, `academic_year`, `graduation_year`) VALUES
(1, 4, 'STU2024001', '2003-05-14', 'Male', 1, 'B.Tech CSE', '2023-2027', 2027),
(2, 5, 'STU2024002', '2003-08-22', 'Female', 1, 'B.Tech CSE', '2023-2027', 2027),
(3, 6, 'STU2024003', '2003-11-03', 'Male', 2, 'B.Tech IT', '2023-2027', 2027),
(4, 7, 'STU2024004', '2003-02-18', 'Female', 2, 'B.Tech IT', '2023-2027', 2027),
(5, 8, 'STU2024005', '2002-09-30', 'Male', 1, 'B.Tech CSE', '2023-2027', 2027),
(6, 9, 'STU2024006', '2003-12-12', 'Female', 4, 'B.Tech AIDS', '2023-2027', 2027),
(7, 10, 'STU2024007', '2003-04-05', 'Male', 3, 'B.E ECE', '2023-2027', 2027),
(8, 11, 'STU2024008', '2003-07-29', 'Female', 4, 'B.Tech AIDS', '2023-2027', 2027),
(9, 12, 'STU2024009', '2002-10-15', 'Male', 3, 'B.E ECE', '2023-2027', 2027),
(10, 13, 'STU2024010', '2003-03-25', 'Female', 2, 'B.Tech IT', '2023-2027', 2027);

-- Academic Records (Diverse performance profiles)
INSERT INTO `academic_records` (`student_id`, `tenth_percentage`, `twelfth_percentage`, `diploma_percentage`, `current_cgpa`, `current_semester`, `num_backlogs`, `cleared_backlogs`, `attendance_percentage`, `sem1_gpa`, `sem2_gpa`, `sem3_gpa`, `sem4_gpa`, `sem5_gpa`, `sem6_gpa`) VALUES
(1, 94.50, 92.00, NULL, 9.25, 6, 0, 0, 95.00, 9.10, 9.20, 9.30, 9.25, 9.35, 9.30),
(2, 90.00, 88.50, NULL, 8.65, 6, 0, 0, 89.50, 8.40, 8.60, 8.70, 8.65, 8.75, 8.80),
(3, 85.00, 81.00, NULL, 7.55, 6, 0, 1, 82.00, 7.20, 7.40, 7.60, 7.50, 7.70, 7.90),
(4, 95.00, 94.00, NULL, 8.90, 6, 0, 0, 92.50, 8.80, 8.90, 8.95, 8.85, 9.00, 8.90),
(5, 72.00, 68.00, NULL, 5.80, 6, 3, 1, 64.00, 6.20, 5.90, 5.60, 5.70, 5.80, 5.60),
(6, 84.00, 80.00, NULL, 7.30, 6, 0, 0, 78.50, 7.00, 7.20, 7.40, 7.30, 7.45, 7.45),
(7, 65.00, 60.00, NULL, 5.15, 6, 4, 0, 58.00, 5.40, 5.20, 5.00, 5.10, 5.10, 5.10),
(8, 92.50, 91.00, NULL, 8.95, 6, 0, 0, 91.00, 8.80, 8.90, 9.00, 8.95, 9.05, 9.00),
(9, 82.00, 78.00, NULL, 7.70, 6, 0, 0, 81.00, 7.50, 7.60, 7.80, 7.70, 7.80, 7.80),
(10, 86.00, 82.00, NULL, 7.20, 6, 1, 0, 68.00, 7.40, 7.30, 7.10, 7.00, 7.20, 7.20);

-- Student Skills Seed Data (Technical + Soft skills ratings for all 10 students)
-- Student 1: Aarav (Top coding & soft skills)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(1, 1, 5), (1, 2, 5), (1, 3, 4), (1, 4, 4), (1, 5, 5), (1, 6, 5), (1, 7, 5), (1, 8, 5), (1, 9, 4), (1, 10, 3), (1, 11, 4), (1, 12, 4), (1, 13, 5), (1, 14, 5),
(1, 15, 5), (1, 16, 5), (1, 17, 4), (1, 18, 5), (1, 19, 4), (1, 20, 4), (1, 21, 5);

-- Student 2: Priya (Strong Web & Java)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(2, 1, 4), (2, 2, 4), (2, 3, 3), (2, 4, 4), (2, 5, 4), (2, 6, 4), (2, 7, 4), (2, 8, 4), (2, 9, 4), (2, 10, 3), (2, 11, 4), (2, 12, 3), (2, 13, 4), (2, 14, 4),
(2, 15, 4), (2, 16, 5), (2, 17, 4), (2, 18, 4), (2, 19, 4), (2, 20, 4), (2, 21, 4);

-- Student 3: Rohan (High programming, moderate soft skills)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(3, 1, 3), (3, 2, 5), (3, 3, 3), (3, 4, 3), (3, 5, 5), (3, 6, 4), (3, 7, 4), (3, 8, 4), (3, 9, 4), (3, 10, 3), (3, 11, 3), (3, 12, 4), (3, 13, 5), (3, 14, 4),
(3, 15, 3), (3, 16, 4), (3, 17, 3), (3, 18, 4), (3, 19, 3), (3, 20, 4), (3, 21, 3);

-- Student 4: Sneha (High academics, beginner programming)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(4, 1, 2), (4, 2, 2), (4, 3, 3), (4, 4, 3), (4, 5, 2), (4, 6, 3), (4, 7, 2), (4, 8, 3), (4, 9, 2), (4, 10, 2), (4, 11, 2), (4, 12, 2), (4, 13, 2), (4, 14, 3),
(4, 15, 4), (4, 16, 4), (4, 17, 3), (4, 18, 3), (4, 19, 4), (4, 20, 3), (4, 21, 4);

-- Student 5: Vikram (Low across the board)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(5, 1, 1), (5, 2, 2), (5, 3, 2), (5, 4, 1), (5, 5, 2), (5, 6, 2), (5, 7, 1), (5, 8, 2), (5, 9, 1), (5, 10, 1), (5, 11, 1), (5, 12, 1), (5, 13, 2), (5, 14, 2),
(5, 15, 2), (5, 16, 2), (5, 17, 1), (5, 18, 2), (5, 19, 1), (5, 20, 2), (5, 21, 2);

-- Student 6: Ananya (Intermediate)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(6, 1, 3), (6, 2, 4), (6, 3, 3), (6, 4, 3), (6, 5, 3), (6, 6, 3), (6, 7, 3), (6, 8, 3), (6, 9, 2), (6, 10, 2), (6, 11, 3), (6, 12, 3), (6, 13, 3), (6, 14, 3),
(6, 15, 3), (6, 16, 4), (6, 17, 3), (6, 18, 3), (6, 19, 3), (6, 20, 3), (6, 21, 3);

-- Student 7: Rahul (Needs Improvement)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(7, 1, 1), (7, 2, 1), (7, 3, 2), (7, 4, 1), (7, 5, 1), (7, 6, 1), (7, 7, 1), (7, 8, 1), (7, 9, 1), (7, 10, 1), (7, 11, 1), (7, 12, 1), (7, 13, 1), (7, 14, 1),
(7, 15, 2), (7, 16, 2), (7, 17, 1), (7, 18, 1), (7, 19, 1), (7, 20, 2), (7, 21, 1);

-- Student 8: Divya (AI/ML & Python Specialist)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(8, 1, 3), (8, 2, 5), (8, 3, 3), (8, 4, 3), (8, 5, 4), (8, 6, 4), (8, 7, 4), (8, 8, 4), (8, 9, 4), (8, 10, 3), (8, 11, 5), (8, 12, 5), (8, 13, 4), (8, 14, 5),
(8, 15, 4), (8, 16, 4), (8, 17, 4), (8, 18, 5), (8, 19, 4), (8, 20, 4), (8, 21, 4);

-- Student 9: Karthik (ECE/Embedded focus)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(9, 1, 2), (9, 2, 3), (9, 3, 5), (9, 4, 4), (9, 5, 2), (9, 6, 3), (9, 7, 3), (9, 8, 3), (9, 9, 2), (9, 10, 2), (9, 11, 3), (9, 12, 3), (9, 13, 2), (9, 14, 4),
(9, 15, 3), (9, 16, 4), (9, 17, 3), (9, 18, 4), (9, 19, 3), (9, 20, 3), (9, 21, 3);

-- Student 10: Tanvi (Good web skills, low attendance)
INSERT INTO `student_skills` (`student_id`, `skill_id`, `rating`) VALUES
(10, 1, 3), (10, 2, 3), (10, 3, 2), (10, 4, 2), (10, 5, 4), (10, 6, 4), (10, 7, 3), (10, 8, 3), (10, 9, 3), (10, 10, 2), (10, 11, 3), (10, 12, 2), (10, 13, 4), (10, 14, 3),
(10, 15, 3), (10, 16, 3), (10, 17, 3), (10, 18, 3), (10, 19, 2), (10, 20, 3), (10, 21, 3);

-- Certifications Seed Data
INSERT INTO `certifications` (`student_id`, `name`, `provider`, `issue_date`, `certification_level`, `credential_url`) VALUES
(1, 'AWS Certified Solutions Architect', 'AWS', '2025-06-15', 'Advanced', 'https://aws.amazon.com/verify/101'),
(1, 'Deep Learning Specialization', 'Coursera', '2025-02-10', 'Advanced', 'https://coursera.org/verify/102'),
(1, 'Programming in Java - Elite+Silver', 'NPTEL', '2024-11-20', 'Intermediate', 'https://nptel.ac.in/verify/103'),
(2, 'Google Cloud Digital Leader', 'Google', '2025-04-12', 'Intermediate', 'https://google.com/verify/201'),
(2, 'Data Structures with Python', 'Infosys Springboard', '2024-09-18', 'Intermediate', 'https://infosys.com/verify/202'),
(3, 'Meta Front-End Developer', 'Coursera', '2025-01-10', 'Intermediate', 'https://coursera.org/verify/301'),
(3, 'Database Management Systems', 'NPTEL', '2024-10-25', 'Intermediate', 'https://nptel.ac.in/verify/302'),
(4, 'Python for Everybody', 'Coursera', '2024-08-15', 'Beginner', 'https://coursera.org/verify/401'),
(6, 'SQL and Relational Databases', 'Coursera', '2024-12-05', 'Beginner', 'https://coursera.org/verify/601'),
(8, 'TensorFlow Developer Certificate', 'Google', '2025-03-22', 'Advanced', 'https://google.com/verify/801'),
(8, 'Machine Learning Specialization', 'Coursera', '2024-11-14', 'Advanced', 'https://coursera.org/verify/802'),
(9, 'Embedded Systems Programming', 'NPTEL', '2025-01-15', 'Intermediate', 'https://nptel.ac.in/verify/901'),
(10, 'Full Stack Web Development Bootcamp', 'Coursera', '2024-10-30', 'Intermediate', 'https://coursera.org/verify/1001');

-- Internships Seed Data
INSERT INTO `internships` (`student_id`, `has_internship`, `company`, `duration_months`, `domain`, `internship_type`, `skills_gained`) VALUES
(1, 1, 'Google Cloud Labs', 3, 'Cloud Infrastructure & SRE', 'Full-time', 'Kubernetes, Go, GCP Cloud Spanner, Terraform'),
(2, 1, 'Infosys Technologies', 2, 'Enterprise Web Solutions', 'Virtual', 'Java Spring Boot, Angular, MySQL, Agile Scrum'),
(3, 1, 'HyperScale Digital (Startup)', 4, 'Full Stack Web Engineering', 'Part-time', 'Next.js, Node.js, PostgreSQL, Tailwind, GraphQL'),
(6, 1, 'Cognizant Technology Solutions', 2, 'Data Quality & Analytics', 'Virtual', 'Python, PowerBI, SQL, ETL validation'),
(8, 1, 'Microsoft Research Labs', 3, 'Computer Vision & GenAI', 'Research', 'PyTorch, Transformers, ONNX, Python'),
(9, 1, 'Bosch Global Software', 2, 'Embedded Automotive Electronics', 'Full-time', 'Embedded C, CAN Protocol, RTOS');

-- Projects Seed Data
INSERT INTO `projects` (`student_id`, `title`, `domain`, `technologies`, `complexity`, `status`, `project_url`) VALUES
(1, 'Distributed Cloud Resource Optimizer', 'Cloud Computing', 'Go, Docker, Kubernetes, Prometheus', 'Advanced', 'Completed', 'https://github.com/aarav/cloud-opt'),
(1, 'AI Powered Code Review Bot', 'Artificial Intelligence', 'Python, OpenAI API, FastAPI, GitHub Actions', 'Advanced', 'Completed', 'https://github.com/aarav/ai-code-reviewer'),
(1, 'Real-time Collaborative Whiteboard', 'Web Development', 'Node.js, WebSockets, Redis, Canvas API', 'Intermediate', 'Completed', 'https://github.com/aarav/collab-board'),
(2, 'Automated Hospital Bed Management', 'Healthcare IT', 'Java Spring Boot, MySQL, Bootstrap 5', 'Intermediate', 'Completed', 'https://github.com/priya/hospital-mgmt'),
(2, 'Smart Recipe Recommender App', 'Mobile & Web', 'React, Node.js, MongoDB', 'Intermediate', 'Completed', 'https://github.com/priya/recipe-app'),
(3, 'Multi-Vendor E-Commerce Platform', 'E-Commerce', 'React, Node.js, Express, Stripe, MySQL', 'Advanced', 'Completed', 'https://github.com/rohan/nexus-ecommerce'),
(3, 'DevConnect - Developer Social Network', 'Social Media', 'Next.js, Tailwind, Supabase', 'Intermediate', 'Completed', 'https://github.com/rohan/devconnect'),
(4, 'Library Management System', 'Desktop / DB', 'Python Tkinter, SQLite', 'Basic', 'Completed', 'https://github.com/sneha/lib-sys'),
(5, 'Basic Student Calculator', 'Utility', 'C++, Console', 'Basic', 'Completed', 'https://github.com/vikram/calc'),
(6, 'Smart Attendance Tracking using QR', 'Education Tech', 'Python Flask, OpenCV, MySQL', 'Intermediate', 'Completed', 'https://github.com/ananya/smart-attend'),
(8, 'Chest X-Ray Pneumonia Detection System', 'Deep Learning & Medical', 'Python, PyTorch, DenseNet, Grad-CAM, FastAPI', 'Advanced', 'Completed', 'https://github.com/divya/pneumonia-ai'),
(8, 'Autonomous Robot Navigation Simulation', 'Robotics & AI', 'ROS2, Python, OpenCV', 'Advanced', 'Completed', 'https://github.com/divya/robot-nav'),
(9, 'Smart Home Energy Meter with IoT', 'Embedded IoT', 'ESP32, Embedded C, MQTT, Node-RED', 'Intermediate', 'Completed', 'https://github.com/karthik/smart-meter'),
(10, 'Personal Portfolio & Blog CMS', 'Web Development', 'PHP, MySQL, JavaScript', 'Intermediate', 'Completed', 'https://github.com/tanvi/portfolio-cms');

-- Extracurricular Activities Seed Data
INSERT INTO `extracurricular_activities` (`student_id`, `activity_type`, `title`, `achievement_level`, `event_date`, `description`) VALUES
(1, 'Hackathons', 'Smart India Hackathon 2024', 'Winner - 1st Prize', '2024-12-20', 'Built automated grievance redressal portal using NLP'),
(1, 'Coding competitions', 'ACM ICPC Regional Contestant', 'Finalist', '2024-10-15', 'Qualified for regional contest representing college'),
(2, 'Project exhibitions', 'National Science Day Tech Expo', 'Runner-up', '2025-02-28', 'Demonstrated automated hospital bed tracker system'),
(2, 'Clubs', 'Secretary, Coding & Innovation Club', 'Lead Coordinator', '2024-08-01', 'Organized technical workshops and competitive coding tests'),
(3, 'Hackathons', 'HackTheFest 36-Hour Hackathon', 'Best UI/UX Award', '2024-09-12', 'Designed modern developer productivity tool'),
(6, 'Volunteering', 'Youth Tech Literacy Drive', 'Volunteer', '2024-07-15', 'Taught basic computer operations and digital safety to schoolchildren'),
(8, 'Paper presentations', 'IEEE International Conference on AI', 'Best Paper Award', '2025-01-20', 'Presented research paper on optimized convolutional models'),
(8, 'Hackathons', 'Kaggle Data Science Hackathon', 'Top 2% Globally', '2024-11-05', 'Engineered predictive pipelines for tabular and image datasets'),
(9, 'Workshops', 'Hands-on Embedded RTOS Workshop', 'Participant', '2024-10-10', 'Completed 3-day rigorous industrial training on FreeRTOS');

-- Performance Analysis Calculated Scores
INSERT INTO `performance_analysis` (`student_id`, `academic_score`, `technical_score`, `career_readiness_score`, `soft_skills_score`, `extracurricular_score`, `overall_score`) VALUES
(1, 95.80, 94.20, 92.50, 93.00, 88.00, 94.00),
(2, 88.40, 81.50, 84.00, 82.50, 75.00, 84.50),
(3, 76.50, 85.00, 78.50, 68.00, 70.00, 78.00),
(4, 91.20, 48.00, 45.00, 70.00, 40.00, 66.50),
(5, 48.50, 32.00, 28.00, 35.00, 20.00, 37.00),
(6, 74.20, 64.00, 66.00, 62.00, 50.00, 68.00),
(7, 42.00, 22.00, 20.00, 28.00, 15.00, 28.50),
(8, 92.80, 91.00, 90.00, 86.00, 85.00, 90.50),
(9, 78.50, 68.00, 72.00, 66.00, 55.00, 72.00),
(10, 65.50, 66.00, 58.00, 58.00, 30.00, 60.50);

-- Predictions Seed Data
INSERT INTO `predictions` (`student_id`, `category`, `confidence_score`, `prediction_notes`) VALUES
(1, 'High Readiness', 96.50, 'Outstanding candidate with stellar academic consistency, deep technical mastery, tier-1 internship, and national hackathon achievements. High likelihood of premier product placement.'),
(2, 'High Readiness', 91.00, 'Strong all-round performer with consistent academic marks, industry internship at Infosys, verified cloud certifications, and good team leadership.'),
(3, 'High Readiness', 84.50, 'Strong practical coder with extensive real-world project portfolio and startup internship experience. Good prospective developer profile.'),
(4, 'Moderate Readiness', 86.00, 'Exceptional theoretical academic record (CGPA 8.9), but lacks practical coding certifications, internship exposure, and domain projects. Recommended practical focus.'),
(5, 'Needs Improvement', 89.00, 'Requires urgent academic support due to 3 active backlogs, sub-70% attendance, and minimal technical project or internship participation.'),
(6, 'Moderate Readiness', 82.00, 'Balanced profile with steady CGPA and positive development. Needs additional industry certifications and 1 advanced capstone project to attain High Readiness.'),
(7, 'Needs Improvement', 92.00, 'Critical academic risk with 4 backlogs, critical attendance deficiency (58%), and absence of foundational skill mastery. Needs faculty mentorship plan.'),
(8, 'High Readiness', 95.00, 'Exemplary specialized profile in AI & Data Science with IEEE research publications, Microsoft Research internship, and advanced project portfolio.'),
(9, 'Moderate Readiness', 83.50, 'Solid hardware-software domain proficiency in embedded systems with verified industrial training. Recommend strengthening software development portfolio.'),
(10, 'Needs Improvement', 85.00, 'Demonstrates competent web programming ability, but low attendance (68%) and 1 active backlog hold back overall placement eligibility.');

-- Recommendations Seed Data
INSERT INTO `recommendations` (`student_id`, `category`, `title`, `description`, `priority`) VALUES
-- Student 1
(1, 'career', 'Apply for Tier-1 Product Engineering Roles', 'Your profile matches criteria for top technology firms. Focus on advanced System Design and LeetCode Hard DSA problems.', 'Low'),
(1, 'projects', 'Publish Open Source Project Artifacts', 'Write engineering blog posts or technical whitepapers explaining your Distributed Optimizer architecture.', 'Low'),
-- Student 4
(4, 'internship', 'Complete Industry Internship or Live Project', 'Lack of practical industry exposure is holding back your profile. Prioritize applying for summer internships or industrial live projects.', 'High'),
(4, 'technical', 'Intensive Data Structures & Coding Practice', 'Enhance hands-on programming by solving 3 problems daily on LeetCode or HackerRank in Java/Python.', 'High'),
(4, 'projects', 'Build at least 2 Full-Stack Domain Projects', 'Develop end-to-end applications with documented GitHub repositories, unit tests, and live deployment.', 'High'),
-- Student 5
(5, 'academic', 'Clear Active Backlogs in Next Exam Cycle', 'Focus on remedial coaching and clearing your 3 pending subjects. Backlogs directly obstruct campus placement eligibility.', 'High'),
(5, 'academic', 'Improve Attendance to Minimum 75%', 'Your current attendance (64%) is below mandatory college standards. Attend all upcoming lectures and lab sessions consistently.', 'High'),
(5, 'technical', 'Fundamentals of Programming & Problem Solving', 'Revisit core C/Java programming concepts and complete beginner modules on Infosys Springboard or Coursera.', 'Medium'),
-- Student 7
(7, 'academic', 'Mandatory Faculty Mentorship & Backlog Clearance', 'Consult your academic advisor weekly to establish a disciplined study timetable for your 4 pending backlog courses.', 'High'),
(7, 'academic', 'Rectify Critical Attendance Deficit', 'Your attendance (58%) will trigger condonation penalties or exam detention. Maintain daily attendance without unexcused leaves.', 'High'),
-- Student 10
(10, 'academic', 'Increase Attendance Above 75% Threshold', 'Attendance of 68% falls below university thresholds. Ensure consistent classroom presence to avoid condonation issues.', 'High'),
(10, 'academic', 'Clear Single Pending Backlog Course', 'Resolve your 1 backlog subject in the upcoming supplementary examination to unlock campus drive eligibility.', 'High'),
(10, 'certifications', 'Earn an Industry-Recognized Cloud Certification', 'Supplement your solid web development experience with an AWS Cloud Practitioner or Meta certification.', 'Medium');

SET FOREIGN_KEY_CHECKS = 1;
