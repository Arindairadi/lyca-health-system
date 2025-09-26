-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 07:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lyca`
--

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_requests`
--

CREATE TABLE `ambulance_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `requester_name` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(64) NOT NULL,
  `area` varchar(512) DEFAULT NULL COMMENT 'Human-readable area / neighborhood',
  `location_text` varchar(512) DEFAULT NULL COMMENT 'Address or landmark',
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `nearest_hospital` varchar(255) DEFAULT NULL,
  `distance_km` decimal(6,2) DEFAULT NULL COMMENT 'Approx distance in kilometres',
  `description` text DEFAULT NULL,
  `status` enum('open','enroute','arrived','resolved','cancelled') NOT NULL DEFAULT 'open',
  `assigned_to` varchar(255) DEFAULT NULL COMMENT 'Optional: ambulance operator name/phone/identifier',
  `secret_key_hash` varchar(255) NOT NULL COMMENT 'Password-hash of the poster keyword used to update status',
  `posted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_public` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ambulance_requests`
--

INSERT INTO `ambulance_requests` (`id`, `slug`, `requester_name`, `is_anonymous`, `phone`, `area`, `location_text`, `latitude`, `longitude`, `nearest_hospital`, `distance_km`, `description`, `status`, `assigned_to`, `secret_key_hash`, `posted_at`, `updated_at`, `is_public`) VALUES
(1, 'central-market-agnes', 'Agnes', 0, '+256700111222', 'Central Market', 'Stall 12 near the fruit vendors', 0.313600, 32.581100, 'St. Mary\'s Clinic', 1.80, 'Unconscious adult, breathing but unresponsive.', 'open', NULL, '$2y$10$LTmI.j9a/TvwF1GSev0mqeNazAdTYylL57NlNB9vPn.huGIQtS.hO', '2025-09-24 19:06:02', '2025-09-24 16:06:02', 1),
(2, 'north-park-req', NULL, 1, '+256700333444', 'North Park', 'Near the playground behind the mall', 0.325000, 32.567000, 'North District Hospital', 4.20, 'Severe bleeding after fall from height.', 'open', NULL, '$2y$10$FdIDomUe2oITeZ1O6JuE8eOfj7jiRRVpSmKaEQhO16Dmj3MRQoT46', '2025-09-24 19:06:02', '2025-09-24 16:06:02', 1),
(3, 'riverside-moses', 'Moses', 0, '+256700555666', 'Riverside', 'Outside 12 Maple Rd, near school gate', 0.298700, 32.590000, 'Riverside Clinic', 2.10, 'Elderly person with chest pain and difficulty breathing.', 'open', NULL, '$2y$10$VPyOO5K5CoUZnGwcKUu3QuXutnRnvvUgH2zUu4wLAGpBGa/OnMPZK', '2025-09-24 19:06:02', '2025-09-24 16:06:02', 1),
(4, 'west-district-linda', 'Linda', 0, '+256700777888', 'West District', 'Corner of Oak St and 5th', 0.305000, 32.560000, 'West General Hospital', 5.60, 'Multiple injured after road traffic collision.', 'open', NULL, '$2y$10$hMieovTLJY0UG8QhTV24QuMQj1TqJJZilJCXwHyU2.ik7ltv4SueS', '2025-09-24 19:06:03', '2025-09-24 16:06:03', 1),
(5, 'hilltop-req', NULL, 1, '+256700999000', 'Hilltop', 'By the water tower', 0.290000, 32.600000, 'Hilltop Health Center', 3.30, 'Person collapsed, possible seizure.', 'open', NULL, '$2y$10$xCp0L8ri79ZGOhasPOvMD.xJoZVBnwIIxg.TPb2g6iJOa3zLbHf6i', '2025-09-24 19:06:03', '2025-09-24 16:06:03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_status_logs`
--

CREATE TABLE `ambulance_status_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `old_status` varchar(32) NOT NULL,
  `new_status` varchar(32) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `changed_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `ip` varbinary(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `name`, `is_anonymous`, `comment`, `created_at`, `approved`, `ip`) VALUES
(1, 1, 'Alice', 0, 'Great overview — very helpful for parents.', '2025-09-24 08:00:00', 1, 0x7f000001),
(2, 1, NULL, 1, 'Thanks for practical tips on when to seek care.', '2025-09-24 08:10:00', 1, 0x7f000001),
(3, 1, 'Bob', 0, 'Would be good to include typical fever thresholds for kids.', '2025-09-24 08:20:00', 1, 0x7f000001),
(4, 1, 'Carol', 0, 'Clear and concise article.', '2025-09-24 08:30:00', 1, 0x7f000001),
(5, 1, NULL, 1, 'Appreciate the safety reminders.', '2025-09-24 08:40:00', 1, 0x7f000001),
(6, 2, 'David', 0, 'Helpful list of foods. Could you add serving examples?', '2025-09-23 08:00:00', 1, 0x7f000001),
(7, 2, NULL, 1, 'Nice summary of supplements.', '2025-09-23 08:10:00', 1, 0x7f000001),
(8, 2, 'Eve', 0, 'Good reminder to prefer whole foods.', '2025-09-23 08:20:00', 1, 0x7f000001),
(9, 2, 'Frank', 0, 'Useful for recovery nutrition.', '2025-09-23 08:30:00', 1, 0x7f000001),
(10, 2, NULL, 1, 'Short and actionable.', '2025-09-23 08:40:00', 1, 0x7f000001),
(11, 3, 'Grace', 0, 'My dentist recommended similar routine.', '2025-09-22 08:00:00', 1, 0x7f000001),
(12, 3, NULL, 1, 'Great tips for kids teeth.', '2025-09-22 08:10:00', 1, 0x7f000001),
(13, 3, 'Heidi', 0, 'Wish I had read this earlier.', '2025-09-22 08:20:00', 1, 0x7f000001),
(14, 3, 'Ivan', 0, 'Good visuals would help.', '2025-09-22 08:30:00', 1, 0x7f000001),
(15, 3, NULL, 1, 'Thanks for sharing.', '2025-09-22 08:40:00', 1, 0x7f000001),
(16, 4, 'Judy', 0, 'Practical strategies for pain management.', '2025-09-21 08:00:00', 1, 0x7f000001),
(17, 4, NULL, 1, 'Would like more on non-medication options.', '2025-09-21 08:10:00', 1, 0x7f000001),
(18, 4, 'Kyle', 0, 'Helpful overview.', '2025-09-21 08:20:00', 1, 0x7f000001),
(19, 4, 'Lena', 0, 'Good references please.', '2025-09-21 08:30:00', 1, 0x7f000001),
(20, 4, NULL, 1, 'Shared with my support group.', '2025-09-21 08:40:00', 1, 0x7f000001),
(21, 5, 'Mallory', 0, 'Sleep tips are practical and doable.', '2025-09-20 08:00:00', 1, 0x7f000001),
(22, 5, NULL, 1, 'Stress reduction section was great.', '2025-09-20 08:10:00', 1, 0x7f000001),
(23, 5, 'Nate', 0, 'I tried the bedtime routine and it helped.', '2025-09-20 08:20:00', 1, 0x7f000001),
(24, 5, 'Olga', 0, 'Would like more on CBT techniques.', '2025-09-20 08:30:00', 1, 0x7f000001),
(25, 5, NULL, 1, 'Clean, readable guidance.', '2025-09-20 08:40:00', 1, 0x7f000001),
(26, 6, 'Paul', 0, 'Concise heart-healthy checklist.', '2025-09-19 08:00:00', 1, 0x7f000001),
(27, 6, NULL, 1, 'Good advice on warning signs.', '2025-09-19 08:10:00', 1, 0x7f000001),
(28, 6, 'Quinn', 0, 'Helpful for family planning.', '2025-09-19 08:20:00', 1, 0x7f000001),
(29, 6, 'Rita', 0, 'Clear and trustworthy.', '2025-09-19 08:30:00', 1, 0x7f000001),
(30, 6, NULL, 1, 'Saved to my reading list.', '2025-09-19 08:40:00', 1, 0x7f000001),
(31, 7, 'Sam', 0, 'Good meal ideas and portion tips.', '2025-09-18 08:00:00', 1, 0x7f000001),
(32, 7, NULL, 1, 'Simple and actionable.', '2025-09-18 08:10:00', 1, 0x7f000001),
(33, 7, 'Tina', 0, 'Could add shopping list examples.', '2025-09-18 08:20:00', 1, 0x7f000001),
(34, 7, 'Uma', 0, 'Great for beginners.', '2025-09-18 08:30:00', 1, 0x7f000001),
(35, 7, NULL, 1, 'Helpful primer.', '2025-09-18 08:40:00', 1, 0x7f000001),
(36, 8, 'Vera', 0, 'Clear red-flag checklist — appreciated.', '2025-09-17 08:00:00', 1, 0x7f000001),
(37, 8, NULL, 1, 'Good quick guide.', '2025-09-17 08:10:00', 1, 0x7f000001),
(38, 8, 'Will', 0, 'Short and useful.', '2025-09-17 08:20:00', 1, 0x7f000001),
(39, 8, 'Xena', 0, 'Helpful for caregivers.', '2025-09-17 08:30:00', 1, 0x7f000001),
(40, 8, NULL, 1, 'Shared with clinic staff.', '2025-09-17 08:40:00', 1, 0x7f000001),
(41, 9, 'Yara', 0, 'Nice myth-busting section.', '2025-09-16 08:00:00', 1, 0x7f000001),
(42, 9, NULL, 1, 'Good clarity on benefits.', '2025-09-16 08:10:00', 1, 0x7f000001),
(43, 9, 'Zack', 0, 'Useful for community outreach.', '2025-09-16 08:20:00', 1, 0x7f000001),
(44, 9, 'Anna', 0, 'Thanks for citing reliable sources.', '2025-09-16 08:30:00', 1, 0x7f000001),
(45, 9, NULL, 1, 'Clear and concise.', '2025-09-16 08:40:00', 1, 0x7f000001),
(46, 10, 'Ben', 0, 'Good tips for exercise and balance.', '2025-09-15 08:00:00', 1, 0x7f000001),
(47, 10, NULL, 1, 'Practical mobility advice.', '2025-09-15 08:10:00', 1, 0x7f000001),
(48, 10, 'Cleo', 0, 'Will share with my parents.', '2025-09-15 08:20:00', 1, 0x7f000001),
(49, 10, 'Damon', 0, 'Nice balance exercise ideas.', '2025-09-15 08:30:00', 1, 0x7f000001),
(50, 10, NULL, 1, 'Helpful and encouraging.', '2025-09-15 08:40:00', 1, 0x7f000001),
(51, 3, NULL, 1, 'HI', '2025-09-24 15:39:57', 1, 0x00000000000000000000000000000001);

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(64) NOT NULL COMMENT 'e.g., accident, epidemic, fire, chemical, other',
  `short_description` varchar(512) DEFAULT NULL,
  `description` text NOT NULL,
  `location_text` varchar(512) DEFAULT NULL COMMENT 'Human-readable location / address',
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `severity` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '1-10 user-reported severity',
  `reported_by` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `contact` varchar(255) DEFAULT NULL,
  `media_url` varchar(1024) DEFAULT NULL,
  `thumbnail_type` enum('url','upload') NOT NULL DEFAULT 'url',
  `thumbnail_url` varchar(1024) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `reported_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `slug`, `title`, `category`, `short_description`, `description`, `location_text`, `latitude`, `longitude`, `severity`, `reported_by`, `is_anonymous`, `contact`, `media_url`, `thumbnail_type`, `thumbnail_url`, `is_published`, `verified`, `reported_at`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 'multi-vehicle-collision-main-st', 'Multi-vehicle collision on Main St', 'accident', 'Several vehicles involved; road partially blocked.', 'A multi-vehicle collision involving three cars occurred at Main St & 4th Ave. Emergency services are on scene; motorists should avoid the area. Reported smoke from one vehicle but no confirmed fire. Expect delays.', 'Main St & 4th Ave (near central pharmacy)', 40.712776, -74.005974, 8, 'John Doe', 0, '555-0101', 'https://example.com/media/collision1.jpg', 'url', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 1, 0, '2025-09-24 09:20:00', NULL, '2025-09-24 15:58:31', '2025-09-24 15:58:31'),
(2, 'suspected-gastro-outbreak-market', 'Suspected gastroenteritis outbreak at Central Market', 'epidemic', 'Multiple attendees reported vomiting and diarrhea after market event.', 'Several people who attended the weekend market reported acute vomiting and diarrhea within hours of eating. Local health department notified and investigating possible foodborne source.', 'Central Market, Hall B', 34.052235, -118.243683, 6, 'Market Vendor', 1, NULL, NULL, 'url', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 1, 0, '2025-09-23 14:40:00', NULL, '2025-09-24 15:58:31', '2025-09-24 15:58:31'),
(3, 'small-chemical-spill-industrial-park', 'Small chemical spill — Industrial Park Unit 7', 'chemical', 'Containment in progress; avoid the immediate area.', 'A minor spill of a cleaning solvent occurred at Industrial Park Unit 7 during a transfer. Fire and hazmat responding; local businesses advised to close windows and avoid the area until clearance.', 'Industrial Park Road, Unit 7', 51.507351, -0.127758, 7, NULL, 1, NULL, NULL, 'url', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 1, 0, '2025-09-22 08:15:00', NULL, '2025-09-24 15:58:31', '2025-09-24 15:58:31'),
(4, 'school-flu-cluster', 'School flu cluster reported', 'epidemic', 'Several students absent with flu-like symptoms; school notifying parents.', 'An increased number of students at Riverside Elementary have fever and cough. School administration is coordinating with the local clinic for screening and advising families on care and testing.', 'Riverside Elementary School, 12 Maple Rd', 37.774929, -122.419416, 5, 'Teacher (Ms. Allen)', 0, 'ms.allen@school.edu', NULL, 'url', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 1, 0, '2025-09-21 07:50:00', NULL, '2025-09-24 15:58:31', '2025-09-24 15:58:31'),
(5, 'major-structure-fire-5th-ave', 'Major structure fire on 5th Ave', 'fire', 'Large fire reported at commercial building; evacuation in progress.', 'A large fire has engulfed a commercial building on 5th Ave. Firefighters are on scene with multiple units; nearby buildings have been evacuated. Roads closed; avoid the area and follow official updates.', '5th Ave & Pine St', 34.000710, -118.805707, 9, 'Observer', 1, NULL, 'https://example.com/media/fire_clip.mp4', 'url', 'https://cdn.pixabay.com/photo/2017/07/23/10/44/dentist-2530990_640.jpg', 1, 0, '2025-09-20 19:10:00', NULL, '2025-09-24 15:58:31', '2025-09-24 15:58:31'),
(6, 'water-contamination-alert-west-district', 'Water contamination alert — West District', 'other', 'Boil-water advisory issued after contamination detected in distribution system.', 'The water utility detected elevated contamination markers in the West District distribution system. A boil-water advisory has been issued; residents should boil drinking water and follow updates from the utility.', 'West District, around Oak Park', 41.878113, -87.629799, 7, 'Utility Watch', 0, 'contact@waterutility.example', NULL, 'url', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 1, 0, '2025-09-19 06:30:00', NULL, '2025-09-24 15:58:31', '2025-09-24 15:58:31');

-- --------------------------------------------------------

--
-- Table structure for table `incident_comments`
--

CREATE TABLE `incident_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `incident_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `ip` varbinary(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incident_comments`
--

INSERT INTO `incident_comments` (`id`, `incident_id`, `name`, `is_anonymous`, `comment`, `created_at`, `approved`, `ip`) VALUES
(1, 1, 'Alice', 0, 'Saw the aftermath — several ambulances were present. Roads blocked for hours.', '2025-09-24 06:35:00', 1, 0x7f000001),
(2, 1, NULL, 1, 'Hope everyone is okay. Witnessed traffic diverted at 3rd Ave.', '2025-09-24 06:40:00', 1, 0x7f000001),
(3, 1, 'Bob', 0, 'Took a different route; thank you for the heads-up.', '2025-09-24 06:45:00', 1, 0x7f000001),
(4, 2, 'Carol', 0, 'My neighbor got sick after the same market stall. Please investigate food vendors closely.', '2025-09-23 12:05:00', 1, 0x7f000001),
(5, 2, NULL, 1, 'Market organizers should post a notice and advise patrons.', '2025-09-23 12:20:00', 1, 0x7f000001),
(6, 2, 'David', 0, 'If this is foodborne it could affect many; hope public health responds quickly.', '2025-09-23 12:40:00', 1, 0x7f000001),
(7, 3, 'Eve', 0, 'Hazmat team was seen arriving. Good that it was contained early.', '2025-09-22 05:25:00', 1, 0x7f000001),
(8, 3, NULL, 1, 'Businesses nearby were told to close windows; smelled a faint chemical odor.', '2025-09-22 05:40:00', 1, 0x7f000001),
(9, 3, 'Frank', 0, 'Stay clear of Industrial Park Road until clearance is given.', '2025-09-22 06:00:00', 1, 0x7f000001),
(10, 4, 'Grace', 0, 'School communicated quickly via SMS. Parents are arranging pickups.', '2025-09-21 05:05:00', 1, 0x7f000001),
(11, 4, NULL, 1, 'My child was sent home as a precaution; clinic is offering rapid checks.', '2025-09-21 05:20:00', 1, 0x7f000001),
(12, 4, 'Heidi', 0, 'If symptoms persist, families should contact their GP or local clinic.', '2025-09-21 05:45:00', 1, 0x7f000001),
(13, 5, 'Ivan', 0, 'Horrific scenes. Fire crews worked through the night.', '2025-09-20 16:35:00', 1, 0x7f000001),
(14, 5, NULL, 1, 'Avoid 5th Ave; traffic being redirected several blocks away.', '2025-09-20 16:50:00', 1, 0x7f000001),
(15, 5, 'Judy', 0, 'Praying for those affected. Any updates on injuries?', '2025-09-20 17:05:00', 1, 0x7f000001),
(16, 6, 'Kyle', 0, 'Utility posted the advisory; boiling water now until further notice.', '2025-09-19 04:00:00', 1, 0x7f000001),
(17, 6, NULL, 1, 'Stores are selling bottled water quickly — stock up if you are in West District.', '2025-09-19 04:25:00', 1, 0x7f000001),
(18, 6, 'Lena', 0, 'Any info on the cause of contamination?', '2025-09-19 04:40:00', 1, 0x7f000001);

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `thumbnail_url` varchar(1024) DEFAULT NULL,
  `thumbnail_type` enum('url','upload') NOT NULL DEFAULT 'url',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `slug`, `title`, `short_description`, `content`, `thumbnail_url`, `thumbnail_type`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'local-health-summit-2025', 'Local Health Summit 2025 Recap', 'Highlights and outcomes from the Local Health Summit 2025.', '<p>The Local Health Summit brought together clinicians and community leaders to discuss prevention and access to care. Key takeaways and next steps are summarized here.</p>', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 'url', 1, '2025-09-24 09:00:00', '2025-09-24 06:00:00', '2025-09-24 06:00:00'),
(2, 'vitamin-research-update', 'Vitamin Research Update', 'New research on vitamin supplementation and public health implications.', '<p>Recent studies examined the role of B vitamins and others in recovery and prevention. We summarize evidence and practical considerations.</p>', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 'url', 1, '2025-09-20 10:00:00', '2025-09-20 07:00:00', '2025-09-20 07:00:00'),
(3, 'dentistry-outreach-program', 'Dentistry Outreach Program Launched', 'A community dental outreach program aims to improve oral health access.', '<p>The outreach program will provide screenings and education at neighborhood centers. Learn how to participate or refer someone in need.</p>', 'https://cdn.pixabay.com/photo/2017/07/23/10/44/dentist-2530990_640.jpg', 'url', 1, '2025-09-18 08:30:00', '2025-09-18 05:30:00', '2025-09-18 05:30:00'),
(4, 'flu-season-prep', 'Preparing for Flu Season', 'Practical community steps to prepare for the upcoming influenza season.', '<p>Flu season preparation includes vaccination clinics, hygiene campaigns, and tips for households to reduce spread.</p>', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 'url', 1, '2025-09-15 09:00:00', '2025-09-15 06:00:00', '2025-09-15 06:00:00'),
(5, 'mental-health-resources', 'New Mental Health Resources Available', 'Local mental health resources and hotlines have been expanded.', '<p>New counselors and telehealth resources are now available. This article lists services, access routes and contact info.</p>', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 'url', 1, '2025-09-12 12:00:00', '2025-09-12 09:00:00', '2025-09-12 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `news_comments`
--

CREATE TABLE `news_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `news_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `ip` varbinary(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_comments`
--

INSERT INTO `news_comments` (`id`, `news_id`, `name`, `is_anonymous`, `comment`, `created_at`, `approved`, `ip`) VALUES
(1, 1, 'Alice', 0, 'Thanks for the recap — great to see community engagement.', '2025-09-24 07:00:00', 1, 0x7f000001),
(2, 1, NULL, 1, 'Useful summary. When is the next meeting?', '2025-09-24 07:12:00', 1, 0x7f000001),
(3, 1, 'Bob', 0, 'Appreciate the notes on access to care.', '2025-09-24 07:30:00', 1, 0x7f000001),
(4, 2, 'Carol', 0, 'Good to see the evidence summarized.', '2025-09-20 08:05:00', 1, 0x7f000001),
(5, 2, NULL, 1, 'Please add references to the original papers.', '2025-09-20 08:20:00', 1, 0x7f000001),
(6, 2, 'Dave', 0, 'Interesting read.', '2025-09-20 08:40:00', 1, 0x7f000001),
(7, 3, 'Eve', 0, 'Happy to volunteer for outreach.', '2025-09-18 06:00:00', 1, 0x7f000001),
(8, 3, NULL, 1, 'How can local clinics sign up?', '2025-09-18 06:20:00', 1, 0x7f000001),
(9, 3, 'Frank', 0, 'Great initiative.', '2025-09-18 06:45:00', 1, 0x7f000001),
(10, 4, 'Grace', 0, 'Thanks for the prep checklist for families.', '2025-09-15 06:30:00', 1, 0x7f000001),
(11, 4, NULL, 1, 'Will clinics have walk-in vaccination hours?', '2025-09-15 06:55:00', 1, 0x7f000001),
(12, 4, 'Heidi', 0, 'Sharing with my local community group.', '2025-09-15 07:10:00', 1, 0x7f000001),
(13, 5, 'Ivan', 0, 'These resources will help many people.', '2025-09-12 09:20:00', 1, 0x7f000001),
(14, 5, NULL, 1, 'Thank you for compiling contact details.', '2025-09-12 09:45:00', 1, 0x7f000001),
(15, 5, 'Judy', 0, 'Valuable information.', '2025-09-12 10:10:00', 1, 0x7f000001);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `thumbnail_url` varchar(1024) DEFAULT NULL,
  `thumbnail_type` enum('url','upload') NOT NULL DEFAULT 'url',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `slug`, `title`, `short_description`, `content`, `thumbnail_url`, `thumbnail_type`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'understanding-fever', 'Understanding Fever: Causes and Care', 'What causes fevers and how to manage them safely at home.', '<p>Fever is a common symptom of infection and inflammation. This article explains common causes, when to seek care, and safe symptomatic measures.</p>', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 'url', 1, '2025-09-24 10:00:00', '2025-09-24 07:00:00', '2025-09-24 07:00:00'),
(2, 'immune-boosting-foods-and-vitamins', 'Immune-Boosting Foods and Vitamins', 'Dietary measures and vitamins that support immune health.', '<p>Good nutrition supports immune function. Learn about vitamin-rich foods, safe supplement use, and evidence-based tips to support recovery.</p>', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 'url', 1, '2025-09-23 10:00:00', '2025-09-23 07:00:00', '2025-09-23 07:00:00'),
(3, 'dental-health-preventive-tips', 'Dental Health: Preventive Care Tips', 'Simple daily habits to keep teeth and gums healthy.', '<p>Dental care prevents cavities and gum disease. This article covers brushing, flossing, diet and when to visit a dentist.</p>', 'https://cdn.pixabay.com/photo/2017/07/23/10/44/dentist-2530990_640.jpg', 'url', 1, '2025-09-22 10:00:00', '2025-09-22 07:00:00', '2025-09-22 07:00:00'),
(4, 'managing-chronic-pain-safely', 'Managing Chronic Pain Safely', 'Approaches to reduce pain and improve function without undue risk.', '<p>Chronic pain is complex. This piece reviews pacing, medications, exercise, and when specialist input is appropriate.</p>', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 'url', 1, '2025-09-21 10:00:00', '2025-09-21 07:00:00', '2025-09-21 07:00:00'),
(5, 'mental-health-stress-and-sleep', 'Mental Health: Stress and Sleep', 'How stress affects sleep and practical strategies to improve rest.', '<p>Sleep and mental health are closely linked. Learn practical steps to improve sleep hygiene and manage daily stress.</p>', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 'url', 1, '2025-09-20 10:00:00', '2025-09-20 07:00:00', '2025-09-20 07:00:00'),
(6, 'heart-health-what-to-know', 'Heart Health: What to Know', 'Basics of heart-healthy living and warning signs to watch for.', '<p>Cardiovascular health depends on lifestyle and risk factor control. This article summarizes prevention, symptoms that need urgent care, and screening.</p>', 'https://cdn.pixabay.com/photo/2017/07/23/10/44/dentist-2530990_640.jpg', 'url', 1, '2025-09-19 10:00:00', '2025-09-19 07:00:00', '2025-09-19 07:00:00'),
(7, 'nutrition-basics-for-healthy-living', 'Nutrition Basics for Healthy Living', 'Key nutrition principles for energy and long-term health.', '<p>Balanced eating supports energy, immunity and disease prevention. This guide covers macronutrients, fiber, and practical meal tips.</p>', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 'url', 1, '2025-09-18 10:00:00', '2025-09-18 07:00:00', '2025-09-18 07:00:00'),
(8, 'recognizing-signs-of-infection', 'Recognizing Signs of Infection', 'How to identify common infections and red flags requiring care.', '<p>Early recognition of infection helps timely treatment. This article outlines common signs, home care, and red flags that need urgent evaluation.</p>', 'https://cdn.pixabay.com/photo/2015/08/02/14/27/vitamin-b-871135_640.jpg', 'url', 1, '2025-09-17 10:00:00', '2025-09-17 07:00:00', '2025-09-17 07:00:00'),
(9, 'vaccination-benefits-and-myths', 'Vaccination: Benefits and Myths', 'Clear facts about vaccines and common misconceptions.', '<p>Vaccination prevents disease. This article reviews how vaccines work, common myths, and when to seek medical advice.</p>', 'https://cdn.pixabay.com/photo/2017/07/23/10/44/dentist-2530990_640.jpg', 'url', 1, '2025-09-16 10:00:00', '2025-09-16 07:00:00', '2025-09-16 07:00:00'),
(10, 'healthy-aging-mobility-and-strength', 'Healthy Aging: Mobility and Strength', 'Practical tips to stay active and maintain independence with age.', '<p>Maintaining mobility and strength supports independence in later life. Discover safe exercises, fall prevention tips, and when to seek physiotherapy.</p>', 'https://cdn.pixabay.com/photo/2015/02/26/15/40/doctor-650534_640.jpg', 'url', 1, '2025-09-15 10:00:00', '2025-09-15 07:00:00', '2025-09-15 07:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ambulance_requests`
--
ALTER TABLE `ambulance_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ambulance_slug` (`slug`),
  ADD KEY `idx_ambulance_status` (`status`),
  ADD KEY `idx_ambulance_posted_at` (`posted_at`);

--
-- Indexes for table `ambulance_status_logs`
--
ALTER TABLE `ambulance_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_request` (`request_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_post` (`post_id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_incidents_slug` (`slug`),
  ADD KEY `idx_incidents_category` (`category`),
  ADD KEY `idx_incidents_reported_at` (`reported_at`);

--
-- Indexes for table `incident_comments`
--
ALTER TABLE `incident_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_incident_comments_incident` (`incident_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_news_slug` (`slug`),
  ADD KEY `idx_news_published_at` (`is_published`,`published_at`);

--
-- Indexes for table `news_comments`
--
ALTER TABLE `news_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_news_comments_news` (`news_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_posts_slug` (`slug`),
  ADD KEY `idx_posts_published_at` (`is_published`,`published_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ambulance_requests`
--
ALTER TABLE `ambulance_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ambulance_status_logs`
--
ALTER TABLE `ambulance_status_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `incident_comments`
--
ALTER TABLE `incident_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `news_comments`
--
ALTER TABLE `news_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ambulance_status_logs`
--
ALTER TABLE `ambulance_status_logs`
  ADD CONSTRAINT `fk_logs_request` FOREIGN KEY (`request_id`) REFERENCES `ambulance_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incident_comments`
--
ALTER TABLE `incident_comments`
  ADD CONSTRAINT `fk_incident_comments_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news_comments`
--
ALTER TABLE `news_comments`
  ADD CONSTRAINT `fk_news_comments_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
