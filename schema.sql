-- ============================================================================
-- Student Information System - database schema (MySQL / MariaDB)
--
-- HOW TO USE (InfinityFree):
--   1. Client Area -> MySQL Databases -> create a database.
--   2. Open phpMyAdmin for that database, choose the "Import" tab and upload this file
--      (the database already exists, so there is no CREATE DATABASE statement here).
--
-- Default login:  username: admin   password: admin123   (change it after first login!)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS student_academics;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sessions;

SET FOREIGN_KEY_CHECKS = 1;

-- System accounts -----------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(50)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,            -- password_hash() output, never plain text
    full_name       VARCHAR(100) NOT NULL,
    role            ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    last_login      DATETIME NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions live in the database so the web tier stays stateless ---------------
CREATE TABLE sessions (
    id            VARCHAR(128) NOT NULL,
    data          MEDIUMTEXT   NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_sessions_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Departments -----------------------------------------------------------------
CREATE TABLE departments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(20)  NOT NULL,
    name        VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Courses (belong to a department; cannot be removed while the department has them)
CREATE TABLE courses (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id INT UNSIGNED NOT NULL,
    code          VARCHAR(20)  NOT NULL,
    name          VARCHAR(150) NOT NULL,
    description   VARCHAR(500) NULL,
    units         SMALLINT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_courses_code (code),
    KEY idx_courses_department (department_id),
    CONSTRAINT fk_courses_department FOREIGN KEY (department_id)
        REFERENCES departments (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Students --------------------------------------------------------------------
CREATE TABLE students (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_roll      VARCHAR(30)  NOT NULL,
    last_name         VARCHAR(60)  NOT NULL,
    first_name        VARCHAR(60)  NOT NULL,
    middle_name       VARCHAR(60)  NULL,
    gender            ENUM('Male','Female','Other') NOT NULL,
    birth_date        DATE NOT NULL,
    contact_no        VARCHAR(20)  NULL,
    present_address   VARCHAR(255) NOT NULL,
    permanent_address VARCHAR(255) NULL,
    status            ENUM('Active','Inactive','Graduated','Dropped','Transferred') NOT NULL DEFAULT 'Active',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_roll (student_roll),
    KEY idx_students_name (last_name, first_name),
    KEY idx_students_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student academic history (deleting a student removes these rows too) -------
CREATE TABLE student_academics (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id   INT UNSIGNED NOT NULL,
    course_id    INT UNSIGNED NOT NULL,
    semester     ENUM('First Semester','Second Semester','Third Semester') NOT NULL,
    school_year  VARCHAR(9)   NOT NULL,                         -- e.g. 2024-2025
    year_level   ENUM('1st Year','2nd Year','3rd Year','4th Year','5th Year') NOT NULL,
    begin_status ENUM('New','Regular','Irregular','Returnee','Transferee') NOT NULL,
    end_status   ENUM('Ongoing','Completed','Dropped','Failed') NOT NULL DEFAULT 'Ongoing',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_academic_term (student_id, semester, school_year),
    KEY idx_academics_course (course_id),
    CONSTRAINT fk_academics_student FOREIGN KEY (student_id)
        REFERENCES students (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_academics_course FOREIGN KEY (course_id)
        REFERENCES courses (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Default data
-- ============================================================================
INSERT INTO users (username, password_hash, full_name, role)
VALUES ('admin', '$2y$10$J0nJ7sa6bhY4e1rSnw7eXOv6MZPMwPGIVogvzkicJlGyZCgoXQjqW', 'Administrator Admin', 'admin');

-- Sample data so the screens are not empty (delete these rows if you do not want them)
INSERT INTO departments (code, name, description) VALUES
 ('CCS',  'College of Computing Studies', 'Computer science and information technology programs'),
 ('CBA',  'College of Business Administration', 'Business and accountancy programs');

INSERT INTO courses (department_id, code, name, units) VALUES
 (1, 'BSIT', 'Bachelor of Science in Information Technology', 160),
 (1, 'BSCS', 'Bachelor of Science in Computer Science', 164),
 (2, 'BSBA', 'Bachelor of Science in Business Administration', 150);

INSERT INTO students (student_roll, last_name, first_name, middle_name, gender, birth_date, contact_no, present_address, permanent_address, status) VALUES
 ('2314100620007', 'Cooper', 'Mark', 'D', 'Male', '2007-06-23', '09123456789', 'This my sample present address.', 'This my sample permanent address.', 'Active');

INSERT INTO student_academics (student_id, course_id, semester, school_year, year_level, begin_status, end_status) VALUES
 (1, 1, 'First Semester',  '2019-2020', '1st Year', 'New',     'Completed'),
 (1, 1, 'Second Semester', '2019-2020', '1st Year', 'Regular', 'Completed'),
 (1, 1, 'First Semester',  '2020-2021', '2nd Year', 'Regular', 'Dropped');
