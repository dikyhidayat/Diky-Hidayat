-- ============================================================
-- database.sql - Sistem Informasi Manajemen Data Mahasiswa
-- Import: mysql -u root -p < database.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS db_mahasiswa
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_mahasiswa;

DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username   VARCHAR(50)  NOT NULL,
  password   VARCHAR(255) NOT NULL COMMENT 'hash dari password_hash()',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mahasiswa (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nim        VARCHAR(20)  NOT NULL,
  nama       VARCHAR(100) NOT NULL,
  jurusan    VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL,
  no_hp      VARCHAR(20)  DEFAULT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mahasiswa_nim (nim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed akun admin  (username: admin | password: password123)
INSERT INTO users (username, password) VALUES
('admin', '$2y$10$fujdX/.ktHqnDdqsZlIDIuDxjreZWygdwNA4Ykd4IioNM/IxIRvCq');

-- Seed data mahasiswa contoh
INSERT INTO mahasiswa (nim, nama, jurusan, email, no_hp) VALUES
('2023110001', 'Andi Pratama',    'Teknik Informatika', 'andi.pratama@example.com',  '081234567801'),
('2023110002', 'Siti Nurhaliza',  'Sistem Informasi',   'siti.nurhaliza@example.com','081234567802'),
('2023110003', 'Budi Santoso',    'Teknik Elektro',     'budi.santoso@example.com',  '081234567803'),
('2023110004', 'Dewi Lestari',    'Manajemen',          'dewi.lestari@example.com',  '081234567804'),
('2023110005', 'Rizky Ramadhan',  'Teknik Informatika', 'rizky.ramadhan@example.com','081234567805');
