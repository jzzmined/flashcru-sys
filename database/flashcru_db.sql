-- ============================================================
--  FlashCru Database — flashcru_db.sql
--  Run in phpMyAdmin or MySQL CLI:
--    mysql -u root -p < flashcru_db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS flashcru_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flashcru_db;

-- ── BARANGAYS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS barangays (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    municipality VARCHAR(100) DEFAULT 'Davao City',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── INCIDENT TYPES ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS incident_types (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    phone        VARCHAR(20),
    barangay_id  INT,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('user','admin') DEFAULT 'user',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── TEAMS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS teams (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    type       VARCHAR(80),
    team_lead  VARCHAR(150),
    status     ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── TEAM MEMBERS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS team_members (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name    VARCHAR(150) NOT NULL,
    role    VARCHAR(80),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── INCIDENTS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS incidents (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    incident_type_id INT NOT NULL,
    barangay_id      INT,
    team_id          INT DEFAULT NULL,
    description      TEXT,
    location_detail  VARCHAR(255),
    status           ENUM('pending','assigned','responding','resolved','cancelled') DEFAULT 'pending',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)          REFERENCES users(id)          ON DELETE CASCADE,
    FOREIGN KEY (incident_type_id) REFERENCES incident_types(id),
    FOREIGN KEY (barangay_id)      REFERENCES barangays(id)      ON DELETE SET NULL,
    FOREIGN KEY (team_id)          REFERENCES teams(id)          ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── REPORT STATUS LOG ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS report_status (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    status      VARCHAR(50) NOT NULL,
    changed_by  INT,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by)  REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── ACTIVITY LOG ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    action     VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--  SAMPLE DATA
-- ============================================================

-- Barangays
INSERT INTO barangays (name) VALUES
('Agdao'),('Bago Aplaya'),('Bago Gallera'),('Buhangin'),('Bunawan'),
('Calinan'),('Daliao'),('Davao Proper'),('Indangan'),('Lapu-lapu'),
('Lizada'),('Lubogan'),('Mandug'),('Matina Aplaya'),('Matina Crossing'),
('Mintal'),('Panacan'),('Poblacion'),('Sasa'),('Talomo'),
('Tibungco'),('Toril'),('Tugbok'),('Ulas'),('Waan');

-- Incident Types
INSERT INTO incident_types (name, description) VALUES
('Road Accident',      'Vehicular accidents involving injuries or property damage'),
('Fire Incident',      'Building or structural fires requiring fire response'),
('Medical Emergency',  'Medical emergencies requiring immediate health intervention'),
('Flooding',           'Flood-related incidents affecting barangay areas'),
('Crime / Security',   'Criminal activity or security threats in the area'),
('Natural Disaster',   'Earthquakes, landslides, or typhoon-related incidents'),
('Missing Person',     'Reports of missing individuals requiring search'),
('Utility Emergency',  'Power outages, gas leaks, or water main breaks'),
('Disturbance',        'Civil disturbances, fights, or public disorder'),
('Rescue Operation',   'Persons trapped, drowning, or in need of rescue');

-- Teams
INSERT INTO teams (name, type, team_lead, status) VALUES
('Alpha Medical Unit',   'Medical',          'Dr. Maria Santos',    'active'),
('Bravo Fire Response',  'Fire',             'Capt. Ramon Cruz',    'active'),
('Charlie Security',     'Security',         'Sgt. Jose Reyes',     'active'),
('Delta Rescue Squad',   'Search & Rescue',  'Lt. Ana Gonzales',    'active'),
('Echo Road Assist',     'Road Assistance',  'Engr. Paolo Lim',     'active');

INSERT INTO team_members (team_id, name, role) VALUES
(1, 'Nurse Carla Bautista',  'Paramedic'),
(1, 'Dr. Renzo Torres',      'Emergency Doctor'),
(2, 'FF Mark Dela Cruz',     'Firefighter'),
(2, 'FF Jane Navarro',       'Firefighter'),
(3, 'PO1 Ben Perez',         'Security Officer'),
(3, 'PO2 Lina Morales',      'Security Officer'),
(4, 'Rescuer Tony Villaluz', 'Search & Rescue Specialist'),
(5, 'Tech. Randy Abad',      'Road Technician');

-- ============================================================
--  DEFAULT ACCOUNTS
--  Both passwords are:  password
--  Generated with: password_hash('password', PASSWORD_DEFAULT)
-- ============================================================

-- Admin account
INSERT INTO users (name, email, phone, password, role) VALUES
('System Admin', 'admin@flashcru.ph', '09000000000',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin');

-- Test user account
INSERT INTO users (name, email, phone, barangay_id, password, role) VALUES
('Juan dela Cruz', 'juan@example.com', '09123456789', 1,
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'user');

-- ============================================================
--  LOGIN CREDENTIALS
--  Admin : admin@flashcru.ph  / password
--  User  : juan@example.com   / password
-- ============================================================