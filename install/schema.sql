-- EMC2 Legal Blog CMS - Esquema de base de datos
-- MySQL 5.7+ / MariaDB 10.2+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `meta_title` VARCHAR(70),
    `meta_description` VARCHAR(170),
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de posts
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `excerpt` TEXT,
    `featured_image` VARCHAR(500),
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    `category_id` INT UNSIGNED,
    `author_id` INT UNSIGNED NOT NULL,
    `meta_title` VARCHAR(70),
    `meta_description` VARCHAR(170),
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    `published_at` DATETIME,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_published_at` (`published_at`),
    KEY `idx_category` (`category_id`),
    KEY `idx_author` (`author_id`),
    CONSTRAINT `fk_post_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_post_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de etiquetas
CREATE TABLE IF NOT EXISTS `tags` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación posts-etiquetas
CREATE TABLE IF NOT EXISTS `post_tags` (
    `post_id` INT UNSIGNED NOT NULL,
    `tag_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`post_id`, `tag_id`),
    CONSTRAINT `fk_pt_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pt_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de medios
CREATE TABLE IF NOT EXISTS `media` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `filepath` VARCHAR(500) NOT NULL,
    `alt_text` VARCHAR(255) DEFAULT '',
    `mime_type` VARCHAR(50) NOT NULL,
    `size` INT UNSIGNED NOT NULL DEFAULT 0,
    `uploaded_by` INT UNSIGNED,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración
CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(50) NOT NULL,
    `value` TEXT,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Categorías iniciales
INSERT INTO `categories` (`name`, `slug`, `description`, `meta_title`, `meta_description`) VALUES
('Extranjería', 'extranjeria', 'Artículos sobre trámites de extranjería en España', 'Extranjería en España | Blog EMC2 Legal', 'Información actualizada sobre trámites de extranjería, permisos de residencia y trabajo en España.'),
('Nacionalidad', 'nacionalidad', 'Todo sobre la nacionalidad española', 'Nacionalidad Española | Blog EMC2 Legal', 'Guías y artículos sobre cómo obtener la nacionalidad española por residencia, opción y carta de naturaleza.'),
('Arraigo', 'arraigo', 'Arraigo social, laboral, familiar y para la formación', 'Arraigo en España | Blog EMC2 Legal', 'Información sobre arraigo social, laboral, familiar y para la formación en España.'),
('Visados', 'visados', 'Visados de estudiante, trabajo y residencia', 'Visados para España | Blog EMC2 Legal', 'Guías sobre visados de estudiante, trabajo, residencia no lucrativa y otros tipos de visado para España.'),
('Derecho Laboral', 'derecho-laboral', 'Despidos, contratos y derechos laborales', 'Derecho Laboral | Blog EMC2 Legal', 'Artículos sobre derechos laborales, despidos, contratos y reclamaciones en España.'),
('Derecho de Familia', 'derecho-familia', 'Divorcios, custodias y reagrupación familiar', 'Derecho de Familia | Blog EMC2 Legal', 'Información sobre divorcios, custodias, reagrupación familiar y otros temas de derecho de familia.'),
('Derecho Penal', 'derecho-penal', 'Defensa penal y asistencia al detenido', 'Derecho Penal | Blog EMC2 Legal', 'Artículos sobre defensa penal, juicios rápidos y asistencia al detenido en España.'),
('Noticias Legales', 'noticias', 'Últimas noticias y cambios legislativos', 'Noticias Legales | Blog EMC2 Legal', 'Últimas noticias sobre cambios legislativos y novedades legales en España.');

-- Configuración por defecto
INSERT INTO `settings` (`key`, `value`) VALUES
('blog_title', 'Blog EMC2 Legal'),
('blog_description', 'Artículos sobre extranjería, nacionalidad y derecho en España'),
('posts_per_page', '9'),
('analytics_code', ''),
('site_url', 'https://emc2legal.com');
