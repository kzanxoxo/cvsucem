

CREATE DATABASE IF NOT EXISTS campus_events CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_events;

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','organizer') NOT NULL DEFAULT 'organizer',
    avatar      VARCHAR(255) DEFAULT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(80) NOT NULL,
    icon  VARCHAR(50) DEFAULT 'calendar',
    color VARCHAR(20) DEFAULT '#1a9e5c'
) ENGINE=InnoDB;

INSERT INTO categories (name, icon, color) VALUES
    ('Academic',       'book',      '#1a9e5c'),
    ('Sports',         'trophy',    '#006633'),
    ('Arts & Culture', 'palette',   '#c9a227'),
    ('Technology',     'cpu',       '#4facfe'),
    ('Health',         'heart',     '#43e97b'),
    ('Social',         'party',     '#fa709a'),
    ('Workshop',       'wrench',    '#c9a227'),
    ('Seminar',        'megaphone', '#a18cd1');

CREATE TABLE IF NOT EXISTS events (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    organizer_id     INT NOT NULL,
    title            VARCHAR(200) NOT NULL,
    description      TEXT,
    category_id      INT DEFAULT NULL,
    event_date       DATE NOT NULL,
    start_time       TIME NOT NULL,
    end_time         TIME DEFAULT NULL,
    location         VARCHAR(200) NOT NULL,
    max_participants INT DEFAULT 0 COMMENT '0 = unlimited',
    image            VARCHAR(255) DEFAULT NULL,
    status           ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
    qr_token         VARCHAR(64) NOT NULL UNIQUE,
    is_featured      TINYINT(1) DEFAULT 0,
    views            INT DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS participants (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    event_id            INT NOT NULL,
    student_name        VARCHAR(100) NOT NULL,
    student_email       VARCHAR(150) NOT NULL,
    student_id          VARCHAR(50) NOT NULL,
    course              VARCHAR(100) DEFAULT NULL,
    year_level          VARCHAR(20) DEFAULT NULL,
    registration_token  VARCHAR(64) NOT NULL UNIQUE,
    attendance_status   ENUM('registered','attended','absent') DEFAULT 'registered',
    checked_in_at       TIMESTAMP NULL DEFAULT NULL,
    registered_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, student_email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role) VALUES
    ('CvSU System Admin', 'admin@cvsu.edu.ph', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO events (organizer_id, title, description, category_id, event_date, start_time, end_time, location, max_participants, status, qr_token, is_featured) VALUES
    (1, 'CvSU Annual Tech Summit 2026',
     'The flagship technology event at Cavite State University. Keynotes, hackathon booths, and industry talks for IT and engineering students across all campuses.',
     4, '2026-06-15', '08:00:00', '17:00:00', 'CvSU International Convention Center, Indang', 400, 'upcoming', SHA2(CONCAT('cvsu-tech-', RAND()), 256), 1),
    (1, 'Lions Week Cultural Festival',
     'Celebrate CvSU spirit with performances, exhibits, and food fairs from student organizations. Open to all colleges.',
     3, '2026-06-20', '09:00:00', '18:00:00', 'University Oval, Indang Campus', 600, 'upcoming', SHA2(CONCAT('cvsu-culture-', RAND()), 256), 1),
    (1, 'Inter-College Sports Fest 2026',
     'Basketball, volleyball, badminton, and track events. Represent your college and compete for the CvSU championship trophy.',
     2, '2026-07-01', '07:00:00', '17:00:00', 'CvSU Gymnasium, Indang Campus', 500, 'upcoming', SHA2(CONCAT('cvsu-sports-', RAND()), 256), 0),
    (1, 'Student Wellness & Mental Health Seminar',
     'Free seminar on student wellness with guidance counselors and resource booths. Sponsored by the Office of Student Affairs.',
     5, '2026-06-10', '13:00:00', '16:00:00', 'Lecture Hall, College of Arts and Sciences', 150, 'upcoming', SHA2(CONCAT('cvsu-wellness-', RAND()), 256), 0);
