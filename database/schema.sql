SET foreign_key_checks = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS reservation_recurrence_weekdays;
DROP TABLE IF EXISTS reservation_recurrences;
DROP TABLE IF EXISTS reservation_subdivision;
DROP TABLE IF EXISTS reservation_schedules;
DROP TABLE IF EXISTS resource_images;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS subdivisions;
DROP TABLE IF EXISTS resources;
DROP TABLE IF EXISTS reservation_categories;
DROP TABLE IF EXISTS resource_categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile ENUM('discente', 'docente', 'tecnico') NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE resource_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_resource_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE reservation_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_reservation_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_resources_resource_category
        FOREIGN KEY (resource_category_id) REFERENCES resource_categories (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    KEY idx_resources_resource_category (resource_category_id)
) ENGINE=InnoDB;

CREATE TABLE subdivisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE KEY uq_subdivisions_resource_name (resource_id, name),
    CONSTRAINT fk_subdivisions_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE resource_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    CONSTRAINT fk_resource_images_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE CASCADE ON UPDATE RESTRICT,
    KEY idx_resource_images_resource (resource_id)
) ENGINE=InnoDB;

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    advisor_id INT NULL,
    resource_id INT NOT NULL,
    reservation_category_id INT NOT NULL,
    status ENUM('pendente', 'deferido', 'indeferido', 'cancelado') NOT NULL DEFAULT 'pendente',
    motive TEXT NOT NULL,
    justification TEXT NULL,
    decided_by INT NULL,
    decided_at DATETIME NULL,
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    KEY idx_reservations_resource_status (resource_id, status),
    KEY idx_reservations_user (user_id),
    KEY idx_reservations_advisor (advisor_id),
    KEY idx_reservations_category (reservation_category_id),
    KEY idx_reservations_decided_by (decided_by),
    KEY idx_reservations_cancelled_by (cancelled_by),
    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reservations_advisor
        FOREIGN KEY (advisor_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reservations_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reservations_category
        FOREIGN KEY (reservation_category_id) REFERENCES reservation_categories (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reservations_decided_by
        FOREIGN KEY (decided_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reservations_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reservation_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    UNIQUE KEY uq_reservation_schedule (reservation_id, scheduled_date, start_time, end_time),
    KEY idx_schedule_conflict (scheduled_date, start_time, end_time, reservation_id),
    CONSTRAINT fk_reservation_schedules_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reservation_subdivision (
    reservation_id INT NOT NULL,
    subdivision_id INT NOT NULL,
    PRIMARY KEY (reservation_id, subdivision_id),
    KEY idx_reservation_subdivision_subdivision (subdivision_id),
    CONSTRAINT fk_reservation_subdivision_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_reservation_subdivision_subdivision
        FOREIGN KEY (subdivision_id) REFERENCES subdivisions (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reservation_recurrences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    UNIQUE KEY uq_reservation_recurrences_reservation (reservation_id),
    CONSTRAINT fk_reservation_recurrences_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reservation_recurrence_weekdays (
    recurrence_id INT NOT NULL,
    weekday TINYINT NOT NULL,
    PRIMARY KEY (recurrence_id, weekday),
    CONSTRAINT fk_recurrence_weekdays_recurrence
        FOREIGN KEY (recurrence_id) REFERENCES reservation_recurrences (id)
        ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    reservation_id INT NULL,
    action ENUM('aprovar', 'indeferir', 'cancelar', 'excluir_recurso', 'email_enviado') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_user (user_id),
    KEY idx_audit_logs_reservation (reservation_id),
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE RESTRICT,
    CONSTRAINT fk_audit_logs_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

SET foreign_key_checks = 1;
