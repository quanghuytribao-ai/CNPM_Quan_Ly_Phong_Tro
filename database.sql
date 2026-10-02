CREATE DATABASE IF NOT EXISTS quan_ly_phong_tro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quan_ly_phong_tro;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS utilities;
DROP TABLE IF EXISTS contracts;
DROP TABLE IF EXISTS tenants;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 full_name VARCHAR(100) NOT NULL,
 role ENUM('landlord','staff','tenant') NOT NULL DEFAULT 'staff',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users(username,password,full_name,role) VALUES
('chutro','$2y$12$HFOoZiV1sn2ZJX2VfvUc2eFikVuZzQgHBMKfbYjCMe7hx0dM7cDry','Chủ trọ','landlord');

CREATE TABLE rooms (
 id INT AUTO_INCREMENT PRIMARY KEY,
 room_number VARCHAR(20) NOT NULL UNIQUE,
 price DECIMAL(12,2) NOT NULL DEFAULT 0,
 max_people INT NOT NULL DEFAULT 2,
 status ENUM('empty','occupied','repair') DEFAULT 'empty',
 note TEXT,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tenants (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NULL UNIQUE,
 full_name VARCHAR(100) NOT NULL,
 phone VARCHAR(20),
 cccd VARCHAR(20),
 hometown VARCHAR(150),
 room_id INT NULL,
 start_date DATE NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_tenant_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_tenant_room FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE contracts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 tenant_id INT NOT NULL,
 room_id INT NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NULL,
 deposit DECIMAL(12,2) DEFAULT 0,
 rent DECIMAL(12,2) DEFAULT 0,
 status ENUM('active','expired','cancelled') DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
 FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE utilities (
 id INT AUTO_INCREMENT PRIMARY KEY,
 room_id INT NOT NULL,
 month_year VARCHAR(7) NOT NULL,
 old_electric DECIMAL(12,2) DEFAULT 0,
 new_electric DECIMAL(12,2) DEFAULT 0,
 electric_price DECIMAL(12,2) DEFAULT 3500,
 old_water DECIMAL(12,2) DEFAULT 0,
 new_water DECIMAL(12,2) DEFAULT 0,
 water_price DECIMAL(12,2) DEFAULT 15000,
 UNIQUE KEY unique_room_month(room_id,month_year),
 FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE invoices (
 id INT AUTO_INCREMENT PRIMARY KEY,
 room_id INT NOT NULL,
 tenant_id INT NULL,
 month_year VARCHAR(7) NOT NULL,
 room_fee DECIMAL(12,2) DEFAULT 0,
 electric_fee DECIMAL(12,2) DEFAULT 0,
 water_fee DECIMAL(12,2) DEFAULT 0,
 service_fee DECIMAL(12,2) DEFAULT 0,
 total DECIMAL(12,2) GENERATED ALWAYS AS(room_fee+electric_fee+water_fee+service_fee) STORED,
 status ENUM('unpaid','paid') DEFAULT 'unpaid',
 paid_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE,
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO rooms(room_number,price,max_people,status) VALUES
('P101',2500000,2,'occupied'),('P102',2500000,2,'occupied'),('P103',2700000,3,'empty'),('P104',2700000,3,'repair'),('P105',3000000,4,'empty'),('P201',2500000,2,'occupied'),('P202',2500000,2,'empty'),('P203',2700000,3,'occupied');
