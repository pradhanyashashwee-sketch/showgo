-- Create database
CREATE DATABASE IF NOT EXISTS show_go_db;
USE show_go_db;

-- =============================================
-- ADMINS TABLE
-- =============================================
CREATE TABLE admins (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- MOVIES TABLE
-- =============================================
CREATE TABLE movies (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    duration_minutes INT(11) NOT NULL,
    description TEXT,
    poster_url VARCHAR(500),
    release_date DATE,
    status ENUM('coming_soon', 'now_showing', 'ended') DEFAULT 'coming_soon',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- SHOWS TABLE (combines shows and showtimes)
-- =============================================
CREATE TABLE shows (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    movie_id INT(11) NOT NULL,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    hall VARCHAR(50) NOT NULL,
    price_per_seat DECIMAL(8,2) NOT NULL,
    available_seats INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    INDEX idx_movie_id (movie_id),
    INDEX idx_show_date (show_date)
);

-- =============================================
-- BOOKINGS TABLE
-- =============================================
CREATE TABLE bookings (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    movie_id INT(11) NOT NULL,
    show_id INT(11) NOT NULL,
    showtime_id INT(11) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    ticket_id VARCHAR(60) UNIQUE NOT NULL,
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled', 'pending') DEFAULT 'confirmed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES shows(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_show_id (show_id),
    INDEX idx_ticket_id (ticket_id)
);

-- =============================================
-- BOOKED_SEATS TABLE (from 'books' in schema)
-- =============================================
CREATE TABLE booked_seats (
    book_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    booking_id INT(11) NOT NULL,
    show_id INT(11) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_show_seat (show_id, seat_number),
    UNIQUE KEY unique_show_seat (show_id, seat_number)
);

ALTER TABLE shows MODIFY COLUMN show_time VARCHAR(20);

-- Insert movies exactly as shown on your homepage
INSERT INTO movies (id, title, duration_minutes, description, poster_url, release_date, status) VALUES
(1, 'Avengers: EndGame', 120, 'The epic conclusion to the Infinity Saga. The Avengers assemble once again to reverse the damage caused by Thanos and restore balance to the universe.', '/images/movies1.jpeg', '2024-01-15', 'now_showing'),
(2, 'Avatar: Fire and Ash', 130, 'Jake Sully and Neytiri return to Pandora for another adventure. Also known as Avatar: Fire and Ice.', '/images/movies2.jpeg', '2024-02-01', 'now_showing'),
(3, 'Bhool Bhulaiyaa 3', 115, 'The third installment of the popular horror-comedy franchise. More laughter, more scares, more confusion!', '/images/movies3.jpeg', '2024-03-20', 'now_showing'),
(4, 'Anaconda', 140, 'Anaconda is an upcoming American action comedy horror film serving as a meta-reboot of the 1997 film Anaconda and stars Paul Rudd and Jack Black.', '/images/movies4.jpg', '2025-01-10', 'coming_soon'),
(5, 'Burn the Silence', 150, 'Break the Silence: The Movie follows the band on and off stage for 14 months during their Love Yourself World Tour.', '/images/movies5.jpeg', '2025-03-04', 'coming_soon');

-- Insert shows with exact timings from your homepage
INSERT INTO shows (movie_id, show_date, show_time, hall, price_per_seat, available_seats) VALUES
-- Avengers: EndGame (movie_id = 1)
(1, CURDATE(), '08:00:00', 'Hall 1', 12.50, 50),
(1, CURDATE(), '15:30:00', 'Hall 1', 15.00, 50),
(1, CURDATE(), '22:00:00', 'Hall 1', 15.00, 50),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 'Hall 1', 12.50, 50),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:30:00', 'Hall 1', 15.00, 50),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '22:00:00', 'Hall 1', 15.00, 50),

-- Avatar: Fire and Ash (movie_id = 2)
(2, CURDATE(), '10:00:00', 'Hall 2', 14.00, 45),
(2, CURDATE(), '13:00:00', 'Hall 2', 14.00, 45),
(2, CURDATE(), '16:00:00', 'Hall 2', 16.00, 45),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Hall 2', 14.00, 45),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 'Hall 2', 14.00, 45),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', 'Hall 2', 16.00, 45),

-- Bhool Bhulaiyaa 3 (movie_id = 3)
(3, CURDATE(), '11:45:00', 'Hall 3', 11.00, 60),
(3, CURDATE(), '14:00:00', 'Hall 3', 11.00, 60),
(3, CURDATE(), '19:30:00', 'Hall 3', 13.00, 60),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:45:00', 'Hall 3', 11.00, 60),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Hall 3', 11.00, 60),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:30:00', 'Hall 3', 13.00, 60);

-- Check movies
SELECT * FROM movies;

-- Check shows with movie titles
SELECT 
    s.id,
    m.title,
    s.show_date,
    s.show_time,
    s.hall,
    s.price_per_seat,
    s.available_seats
FROM shows s
JOIN movies m ON s.movie_id = m.id
ORDER BY s.show_date, s.show_time;

-- =============================================
-- COMPLETE INSERT SCRIPT FOR SHOWGO DATABASE
-- =============================================

USE showgo_db;

-- 1. INSERT ADMINS (Plain Text Passwords)
INSERT INTO admins (full_name, email, password, created_at) VALUES
('System Administrator', 'admin@showgo.com', 'admin123', NOW()),
('Content Manager', 'content@showgo.com', 'content456', NOW()),
('Finance Manager', 'finance@showgo.com', 'finance789', NOW()),
('Support Staff', 'support@showgo.com', 'support123', NOW()),
('Super Admin', 'superadmin@showgo.com', 'super@123', NOW());

-- 2. INSERT USERS (Plain Text Passwords)
INSERT INTO users (full_name, email, password, created_at) VALUES
('John Doe', 'john.doe@email.com', 'john123', NOW()),
('Jane Smith', 'jane.smith@email.com', 'jane456', NOW()),
('Michael Johnson', 'michael.j@email.com', 'michael789', NOW()),
('Emily Williams', 'emily.w@email.com', 'emily123', NOW()),
('David Brown', 'david.b@email.com', 'david456', NOW()),
('Sarah Davis', 'sarah.d@email.com', 'sarah789', NOW()),
('Robert Miller', 'robert.m@email.com', 'robert123', NOW()),
('Lisa Garcia', 'lisa.g@email.com', 'lisa456', NOW()),
('James Wilson', 'james.w@email.com', 'james789', NOW()),
('Jennifer Taylor', 'jennifer.t@email.com', 'jennifer123', NOW()),
('Test User', 'test@email.com', 'test123', NOW()),
('Demo User', 'demo@email.com', 'demo123', NOW());

-- 3. INSERT MOVIES (from your homepage)
INSERT INTO movies (title, duration_minutes, description, poster_url, release_date, status) VALUES
('Avengers: EndGame', 120, 'The epic conclusion to the Infinity Saga. The Avengers assemble once again to reverse the damage caused by Thanos and restore balance to the universe.', 'images/movies1.jpeg', '2024-01-15', 'now_showing'),
('Avatar: Fire and Ash', 130, 'Jake Sully and Neytiri return to Pandora for another adventure. Also known as Avatar: Fire and Ice.', 'images/movies2.jpeg', '2024-02-01', 'now_showing'),
('Bhool Bhulaiyaa 3', 115, 'The third installment of the popular horror-comedy franchise. More laughter, more scares, more confusion!', 'images/movies3.jpeg', '2024-03-20', 'now_showing'),
('Anaconda', 140, 'Anaconda is an upcoming American action comedy horror film serving as a meta-reboot of the 1997 film Anaconda and stars Paul Rudd and Jack Black.', 'images/movies4.jpg', '2025-01-10', 'coming_soon'),
('Burn the Silence', 150, 'Break the Silence: The Movie follows the band on and off stage for 14 months during their Love Yourself World Tour.', 'images/movies5.jpeg', '2025-03-04', 'coming_soon');

-- 4. INSERT SHOWS (for today and tomorrow)
-- Avengers: EndGame Shows
INSERT INTO shows (movie_id, show_date, show_time, hall, price_per_seat, available_seats) VALUES
(1, CURDATE(), '08:00:00', 'Hall 1', 12.50, 50),
(1, CURDATE(), '15:30:00', 'Hall 1', 15.00, 50),
(1, CURDATE(), '22:00:00', 'Hall 1', 15.00, 50),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 'Hall 1', 12.50, 50),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:30:00', 'Hall 1', 15.00, 50),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '22:00:00', 'Hall 1', 15.00, 50);

-- Avatar: Fire and Ash Shows
INSERT INTO shows (movie_id, show_date, show_time, hall, price_per_seat, available_seats) VALUES
(2, CURDATE(), '10:00:00', 'Hall 2', 14.00, 45),
(2, CURDATE(), '13:00:00', 'Hall 2', 14.00, 45),
(2, CURDATE(), '16:00:00', 'Hall 2', 16.00, 45),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Hall 2', 14.00, 45),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 'Hall 2', 14.00, 45),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', 'Hall 2', 16.00, 45);

-- Bhool Bhulaiyaa 3 Shows
INSERT INTO shows (movie_id, show_date, show_time, hall, price_per_seat, available_seats) VALUES
(3, CURDATE(), '11:45:00', 'Hall 3', 11.00, 60),
(3, CURDATE(), '14:00:00', 'Hall 3', 11.00, 60),
(3, CURDATE(), '19:30:00', 'Hall 3', 13.00, 60),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:45:00', 'Hall 3', 11.00, 60),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Hall 3', 11.00, 60),
(3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:30:00', 'Hall 3', 13.00, 60);

-- 5. INSERT SAMPLE BOOKINGS
INSERT INTO bookings (user_id, movie_id, show_id, showtime_id, seat_number, price, ticket_id, status, booked_at) VALUES
(1, 1, 1, 1, 'A10', 12.50, CONCAT('TK', UNIX_TIMESTAMP(), 'A10'), 'confirmed', NOW()),
(2, 2, 7, 7, 'B05', 14.00, CONCAT('TK', UNIX_TIMESTAMP(), 'B05'), 'confirmed', NOW()),
(3, 3, 13, 13, 'C12', 11.00, CONCAT('TK', UNIX_TIMESTAMP(), 'C12'), 'confirmed', NOW());

-- 6. INSERT BOOKED SEATS
INSERT INTO booked_seats (booking_id, show_id, seat_number) VALUES
(1, 1, 'A10'),
(2, 7, 'B05'),
(3, 13, 'C12');

-- 7. UPDATE AVAILABLE SEATS
UPDATE shows SET available_seats = available_seats - 1 WHERE id IN (1, 7, 13);

-- =============================================
-- VERIFICATION QUERIES
-- =============================================
SELECT 'ADMINS' AS 'TABLE', COUNT(*) AS 'COUNT' FROM admins
UNION ALL
SELECT 'USERS', COUNT(*) FROM users
UNION ALL
SELECT 'MOVIES', COUNT(*) FROM movies
UNION ALL
SELECT 'SHOWS', COUNT(*) FROM shows
UNION ALL
SELECT 'BOOKINGS', COUNT(*) FROM bookings
UNION ALL
SELECT 'BOOKED_SEATS', COUNT(*) FROM booked_seats;