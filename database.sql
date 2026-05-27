-- 1. Table structure for table `settings`
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `monthly_budget` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table structure for table `scholarship_table`
DROP TABLE IF EXISTS `scholarship_table`;
CREATE TABLE `scholarship_table` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `grade_from` DECIMAL(4,2) NOT NULL,
  `grade_to` DECIMAL(4,2) NOT NULL,
  `amount` DECIMAL(8,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table structure for table `students`
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `personal_code` VARCHAR(20) NOT NULL,
  `class_group` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_personal_code` (`personal_code`) -- CRITICAL: Prevents duplicate students on re-imports
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table structure for table `subjects`
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `category` ENUM('VIMP', 'PROF') NOT NULL, -- Forces strict configuration types
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subject_name` (`name`) -- CRITICAL: Prevents duplicate subjects on re-imports
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table structure for table `grades`
DROP TABLE IF EXISTS `grades`;
CREATE TABLE `grades` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `subject_id` INT DEFAULT NULL, -- Relational Link instead of text names
  `grade_type` VARCHAR(100) NOT NULL, -- e.g., 'I semestra vērtējums', 'Galīgais vērtējums'
  `grade` VARCHAR(5) DEFAULT NULL, -- Changed from DECIMAL to VARCHAR to support 'nv' marks smoothly!
  `grade_date` DATE NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_subject_id` (`subject_id`),
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
