-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 19 sep. 2025 à 16:58
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `attendance_system`
--

-- --------------------------------------------------------

--
-- Structure de la table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `timetable_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Absent','Present') NOT NULL DEFAULT 'Absent',
  `attendance_period` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = 1ère partie, 2 = 2ème partie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `timetable_id`, `attendance_date`, `status`, `attendance_period`) VALUES
(4, 1, 3, '2025-09-10', 'Absent', 1),
(5, 6, 12, '2025-09-13', 'Absent', 1),
(6, 7, 12, '2025-09-13', 'Absent', 1),
(7, 4, 12, '2025-09-13', 'Absent', 1),
(9, 6, 12, '2025-09-13', 'Present', 1),
(10, 5, 12, '2025-09-13', 'Present', 1),
(11, 7, 12, '2025-09-13', 'Present', 1),
(12, 12, 12, '2025-09-13', 'Present', 1),
(13, 10, 12, '2025-09-13', 'Present', 1),
(14, 9, 12, '2025-09-13', 'Present', 1),
(15, 8, 12, '2025-09-13', 'Present', 1),
(16, 4, 12, '2025-09-13', 'Present', 1),
(17, 11, 12, '2025-09-13', 'Present', 1),
(19, 6, 12, '2025-09-13', 'Present', 1),
(20, 5, 12, '2025-09-13', 'Absent', 1),
(21, 7, 12, '2025-09-13', 'Absent', 1),
(22, 12, 12, '2025-09-13', 'Present', 1),
(23, 10, 12, '2025-09-13', 'Present', 1),
(24, 9, 12, '2025-09-13', 'Present', 1),
(25, 8, 12, '2025-09-13', 'Absent', 1),
(26, 4, 12, '2025-09-13', 'Present', 1),
(27, 11, 12, '2025-09-13', 'Present', 1),
(30, 6, 12, '2025-09-13', 'Present', 1),
(31, 6, 12, '2025-09-13', 'Present', 2),
(32, 5, 12, '2025-09-13', 'Present', 1),
(33, 5, 12, '2025-09-13', 'Absent', 2),
(34, 7, 12, '2025-09-13', 'Present', 1),
(35, 7, 12, '2025-09-13', 'Present', 2),
(36, 12, 12, '2025-09-13', 'Present', 1),
(37, 12, 12, '2025-09-13', 'Absent', 2),
(38, 10, 12, '2025-09-13', 'Absent', 1),
(39, 10, 12, '2025-09-13', 'Absent', 2),
(40, 9, 12, '2025-09-13', 'Present', 1),
(41, 9, 12, '2025-09-13', 'Present', 2),
(42, 8, 12, '2025-09-13', 'Present', 1),
(43, 8, 12, '2025-09-13', 'Present', 2),
(44, 4, 12, '2025-09-13', 'Absent', 1),
(45, 4, 12, '2025-09-13', 'Present', 2),
(46, 11, 12, '2025-09-13', 'Present', 1),
(47, 11, 12, '2025-09-13', 'Present', 2),
(50, 6, 6, '2025-09-15', 'Present', 1),
(51, 6, 6, '2025-09-15', 'Present', 2),
(52, 5, 6, '2025-09-15', 'Absent', 1),
(53, 5, 6, '2025-09-15', 'Absent', 2),
(54, 7, 6, '2025-09-15', 'Present', 1),
(55, 7, 6, '2025-09-15', 'Present', 2),
(56, 12, 6, '2025-09-15', 'Present', 1),
(57, 12, 6, '2025-09-15', 'Present', 2),
(58, 10, 6, '2025-09-15', 'Present', 1),
(59, 10, 6, '2025-09-15', 'Present', 2),
(60, 9, 6, '2025-09-15', 'Present', 1),
(61, 9, 6, '2025-09-15', 'Present', 2),
(62, 8, 6, '2025-09-15', 'Present', 1),
(63, 8, 6, '2025-09-15', 'Present', 2),
(64, 4, 6, '2025-09-15', 'Present', 1),
(65, 4, 6, '2025-09-15', 'Present', 2),
(66, 11, 6, '2025-09-15', 'Present', 1),
(67, 11, 6, '2025-09-15', 'Present', 2),
(70, 6, 4, '2025-09-15', 'Absent', 1),
(71, 6, 4, '2025-09-15', 'Present', 2),
(72, 5, 4, '2025-09-15', 'Present', 1),
(73, 5, 4, '2025-09-15', 'Present', 2),
(74, 7, 4, '2025-09-15', 'Present', 1),
(75, 7, 4, '2025-09-15', 'Present', 2),
(76, 12, 4, '2025-09-15', 'Present', 1),
(77, 12, 4, '2025-09-15', 'Absent', 2),
(78, 10, 4, '2025-09-15', 'Present', 1),
(79, 10, 4, '2025-09-15', 'Present', 2),
(80, 9, 4, '2025-09-15', 'Present', 1),
(81, 9, 4, '2025-09-15', 'Present', 2),
(82, 8, 4, '2025-09-15', 'Present', 1),
(83, 8, 4, '2025-09-15', 'Present', 2),
(84, 4, 4, '2025-09-15', 'Absent', 1),
(85, 4, 4, '2025-09-15', 'Present', 2),
(86, 11, 4, '2025-09-15', 'Present', 1),
(87, 11, 4, '2025-09-15', 'Present', 2),
(90, 6, 4, '2025-09-15', 'Absent', 1),
(91, 6, 4, '2025-09-15', 'Present', 2),
(92, 5, 4, '2025-09-15', 'Present', 1),
(93, 5, 4, '2025-09-15', 'Present', 2),
(94, 7, 4, '2025-09-15', 'Present', 1),
(95, 7, 4, '2025-09-15', 'Present', 2),
(96, 12, 4, '2025-09-15', 'Present', 1),
(97, 12, 4, '2025-09-15', 'Present', 2),
(98, 10, 4, '2025-09-15', 'Present', 1),
(99, 10, 4, '2025-09-15', 'Present', 2),
(100, 9, 4, '2025-09-15', 'Present', 1),
(101, 9, 4, '2025-09-15', 'Present', 2),
(102, 8, 4, '2025-09-15', 'Absent', 1),
(103, 8, 4, '2025-09-15', 'Present', 2),
(104, 4, 4, '2025-09-15', 'Present', 1),
(105, 4, 4, '2025-09-15', 'Absent', 2),
(106, 11, 4, '2025-09-15', 'Present', 1),
(107, 11, 4, '2025-09-15', 'Present', 2),
(110, 6, 6, '2025-09-15', 'Present', 1),
(111, 6, 6, '2025-09-15', 'Present', 2),
(112, 5, 6, '2025-09-15', 'Present', 1),
(113, 5, 6, '2025-09-15', 'Present', 2),
(114, 7, 6, '2025-09-15', 'Absent', 1),
(115, 7, 6, '2025-09-15', 'Present', 2),
(116, 12, 6, '2025-09-15', 'Present', 1),
(117, 12, 6, '2025-09-15', 'Present', 2),
(118, 10, 6, '2025-09-15', 'Absent', 1),
(119, 10, 6, '2025-09-15', 'Absent', 2),
(120, 9, 6, '2025-09-15', 'Present', 1),
(121, 9, 6, '2025-09-15', 'Present', 2),
(122, 8, 6, '2025-09-15', 'Present', 1),
(123, 8, 6, '2025-09-15', 'Present', 2),
(124, 4, 6, '2025-09-15', 'Absent', 1),
(125, 4, 6, '2025-09-15', 'Absent', 2),
(126, 11, 6, '2025-09-15', 'Present', 1),
(127, 11, 6, '2025-09-15', 'Present', 2),
(148, 6, 12, '2025-09-15', 'Present', 1),
(149, 6, 12, '2025-09-15', 'Absent', 2),
(150, 5, 12, '2025-09-15', 'Absent', 1),
(151, 5, 12, '2025-09-15', 'Present', 2),
(152, 7, 12, '2025-09-15', 'Absent', 1),
(153, 7, 12, '2025-09-15', 'Absent', 2),
(154, 12, 12, '2025-09-15', 'Absent', 1),
(155, 12, 12, '2025-09-15', 'Absent', 2),
(156, 10, 12, '2025-09-15', 'Absent', 1),
(157, 10, 12, '2025-09-15', 'Absent', 2),
(158, 9, 12, '2025-09-15', 'Absent', 1),
(159, 9, 12, '2025-09-15', 'Absent', 2),
(160, 8, 12, '2025-09-15', 'Present', 1),
(161, 8, 12, '2025-09-15', 'Present', 2),
(162, 4, 12, '2025-09-15', 'Absent', 1),
(163, 4, 12, '2025-09-15', 'Absent', 2),
(164, 11, 12, '2025-09-15', 'Absent', 1),
(165, 11, 12, '2025-09-15', 'Absent', 2),
(166, 6, 12, '2025-09-18', 'Present', 1),
(167, 6, 12, '2025-09-18', 'Present', 2),
(168, 5, 12, '2025-09-18', 'Present', 1),
(169, 5, 12, '2025-09-18', 'Present', 2),
(170, 7, 12, '2025-09-18', 'Present', 1),
(171, 7, 12, '2025-09-18', 'Present', 2),
(172, 12, 12, '2025-09-18', 'Present', 1),
(173, 12, 12, '2025-09-18', 'Present', 2),
(174, 10, 12, '2025-09-18', 'Present', 1),
(175, 10, 12, '2025-09-18', 'Present', 2),
(176, 9, 12, '2025-09-18', 'Present', 1),
(177, 9, 12, '2025-09-18', 'Present', 2),
(178, 8, 12, '2025-09-18', 'Present', 1),
(179, 8, 12, '2025-09-18', 'Present', 2),
(180, 4, 12, '2025-09-18', 'Present', 1),
(181, 4, 12, '2025-09-18', 'Present', 2),
(182, 11, 12, '2025-09-18', 'Present', 1),
(183, 11, 12, '2025-09-18', 'Present', 2);

-- --------------------------------------------------------

--
-- Structure de la table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`) VALUES
(1, '1ère année G.info'),
(4, 'G.Informatique'),
(5, 'Genie Indus'),
(7, 'Classes Préparatoires - 2ème année'),
(9, 'Classes Préparatoires - 1ère année'),
(10, 'Génie Informatique - 1ère année'),
(11, 'Génie Informatique - 2ème année'),
(12, 'Génie Informatique - 3ème année'),
(13, 'Génie Industriel - 1ère année'),
(14, 'Génie Industriel - 2ème année'),
(15, 'Génie Électrique - 3ème année');

-- --------------------------------------------------------

--
-- Structure de la table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `class_id`) VALUES
(1, '1', 1),
(4, '1 ere annee G.informatique', 4),
(6, 'G1 1 ere annee', 5),
(7, 'G1', 1),
(8, 'G1', 9),
(9, 'G1', 15);

-- --------------------------------------------------------

--
-- Structure de la table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `modules`
--

INSERT INTO `modules` (`module_id`, `module_name`, `class_id`) VALUES
(8, 'français', 1),
(9, 'anglais', 1),
(11, 'programation', 1),
(12, 'mathématique appliqué', 1),
(13, 'outils informatique', 1),
(14, 'ANGLAIS ', 4),
(15, 'conception SI et SGBD', 4),
(16, 'Technologie WEB', 4),
(17, 'POO', 4),
(18, 'Culture et poesie', 4),
(19, 'Programmation Python', 4),
(20, 'Communication Francais', 4),
(21, 'Systeme dexploitation et reseau', 4),
(24, 'data science', 4);

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`review_id`, `teacher_id`, `role`, `comment`, `created_at`) VALUES
(1, 5, 'teacher', 'ttrtrh', '2025-09-17 00:07:27');

-- --------------------------------------------------------

--
-- Structure de la table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `parent_email` varchar(255) NOT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `students`
--

INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `parent_email`, `group_id`) VALUES
(1, 'sami ', 'hilali', 'shilali003@gmail.com', 1),
(3, 'yassir', 'mani', 'mani.yassir@gmail.com', 1),
(4, 'yassir', 'mani', 'maniyasir@gmail.com', 4),
(5, 'sami', 'hilali', 'sami@gmail.com', 4),
(6, 'siham ', 'hammo', 'siham@gmail.com', 4),
(7, 'Ihssan', 'ibrahimi', 'ibrahimi@gmailcom', 4),
(8, 'aya', 'maalem', 'aya@gmail.com', 4),
(9, 'meryem', 'lbyad', 'lbyad@gmail.com', 4),
(10, 'nada', 'jit', 'jit@gmail.com', 4),
(11, 'hamza', 'tiago', 'hamza@gmail.com', 4),
(12, 'mohammed', 'izem', 'izem@gmail.com', 4);

-- --------------------------------------------------------

--
-- Structure de la table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'teacher'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `first_name`, `last_name`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'User', 'admin@school.com', '$2y$10$DcpAsSBN/s5XANKPSsbe7.HVQlnXjIXkZV..PcRp8haxeLkEwpdIO', 'admin'),
(3, 'Mme', 'BOUREKADI', 'bourekadi@gmail.com', '$2y$10$OKMg4uYrmhUltbyVN0dAzexz6/sA.YbsOLXvU0/wdRpisyDnzwFwy', 'teacher'),
(4, 'Mme', 'KERROM', 'kerroum@gmail.com', '$2y$10$j6UAlCaIE9suVpWh4itXbOhwZKU6us7E.dPx8xv2TsHLmyZIkCQyy', 'teacher'),
(5, 'M', 'KHALD', 'khald@gmail.com', '$2y$10$0EU51eOxFLDIN66UCc8Ce.VTyodqjvj2eKDwYxp7dY/.NY5TlZyBi', 'teacher'),
(6, 'M', 'CHERGUI', 'chergui@gmail.com', '$2y$10$eVc1KVqgEhVjOiMVOTJSueE19/IVUj/.KxumGQ9suCEMeRcN3mYJW', 'teacher'),
(7, 'M', 'SAIDI', 'saidi@gmail.com', '$2y$10$I3Gh8x50h4Fb7Rrc8YF.k.cqYskAhPzjXDARJePtn5ftsArri3lWe', 'teacher'),
(8, 'M', 'ESSALHI', 'essalhi@gmail.com', '$2y$10$isMt.IW5log.4Tl7fm6Fde8f56c5VvoBxgYWNoFJEYRkLEDaDYw26', 'teacher'),
(10, 'yassir', 'mani', 'mani.yassir@school.com', '$2y$10$SN2kkCULO/FuB9wh9XpTOedyk1zoUl5Uap2oPgHW1RABarqo.8LmW', 'professeur');

-- --------------------------------------------------------

--
-- Structure de la table `timetable`
--

CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `timetable`
--

INSERT INTO `timetable` (`timetable_id`, `group_id`, `module_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(3, 1, 9, 1, 'Wednesday', '09:30:00', '10:30:00'),
(4, 4, 14, 3, 'Monday', '14:00:00', '17:30:00'),
(5, 4, 15, 4, 'Tuesday', '08:30:00', '12:00:00'),
(6, 4, 16, 5, 'Tuesday', '14:00:00', '17:30:00'),
(7, 4, 17, 4, 'Wednesday', '14:00:00', '17:30:00'),
(8, 4, 18, 6, 'Thursday', '08:30:00', '12:00:00'),
(9, 4, 19, 7, 'Thursday', '14:00:00', '17:30:00'),
(10, 4, 20, 8, 'Friday', '08:30:00', '12:00:00'),
(11, 4, 21, 4, 'Friday', '14:00:00', '17:30:00'),
(12, 4, 16, 5, 'Saturday', '08:00:00', '12:00:00');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `timetable_id` (`timetable_id`);

--
-- Index pour la table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`);

--
-- Index pour la table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `groups_ibfk_1` (`class_id`);

--
-- Index pour la table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Index pour la table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Index pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT pour la table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`timetable_id`) REFERENCES `timetable` (`timetable_id`);

--
-- Contraintes pour la table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Contraintes pour la table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`),
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`),
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
