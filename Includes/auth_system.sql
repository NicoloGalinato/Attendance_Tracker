-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 28, 2025 at 11:10 PM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u865665685_cxi_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `absenteeism`
--

CREATE TABLE `absenteeism` (
  `id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `operation_manager` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_of_absent` date NOT NULL,
  `follow_call_in_procedure` enum('Yes','No') NOT NULL,
  `sanction` text DEFAULT NULL,
  `reason` text NOT NULL,
  `coverage` varchar(100) DEFAULT NULL,
  `coverage_type` enum('-','NO NEED','TRAINEE','BACK UP','PENDING','DSOT','RDOT','AGENT MODE') NOT NULL,
  `shift` varchar(50) NOT NULL,
  `ir_form` varchar(100) DEFAULT NULL,
  `timestamp` varchar(20) DEFAULT NULL,
  `sub_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absenteeism`
--

INSERT INTO `absenteeism` (`id`, `month`, `employee_id`, `full_name`, `department`, `supervisor`, `operation_manager`, `email`, `date_of_absent`, `follow_call_in_procedure`, `sanction`, `reason`, `coverage`, `coverage_type`, `shift`, `ir_form`, `timestamp`, `sub_name`, `created_at`, `email_sent`, `email_sent_at`) VALUES
(15, 'Jul 2025', 'CXI01059', 'MARCELINO, MICHELLE', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'marcelinomichelle1@gmail.com', '2025-07-26', 'No', 'ABSENCE / NCNS / CWD', 'NCNS', 'ARGOSO, ALFREDO ODRONIA', 'DSOT', '5:00 AM - 2:00 PM', 'NO NEED', '11:30 AM', 'SLT NICO', '2025-07-26 03:30:25', 0, NULL),
(16, 'Jul 2025', 'CXI11684', 'BELOSTRINO, JEROME PAGUYO', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'Jerome.Belostrino@corpay.com', '2025-07-26', 'Yes', 'ABSENCE - VIBER 9:39 AM JULY 26, 2025', 'DUE TO EXPERIENCING SEVERE HEADACHE AND FLU SYMPTOMS', 'RUBI, MYLYN CAROLLA', 'DSOT', '2:00 PM - 11:00 PM', 'PENDING / JULY 28 2:00 PM', '11:31 AM', 'SLT NICO', '2025-07-26 03:31:43', 0, NULL),
(22, 'Jul 2025', 'CXI00790', 'SAMBERI, DANILO ALBISO', 'CLC - RESERVATIONS', 'PARIL, MARWIN', 'Benedict Mendoza', 'danilosamberi2002@gmail.com', '2025-07-29', 'No', 'ABSENCE / NCNS / CWD (LATE ADVISED) - VIBER 11:55: PM JULY 28, 2025', 'DUE TO FEVER AND HEADACHE', 'PENDING', 'PENDING', '11:00 PM - 8:00 AM', 'for ir', '4:28 AM', 'SLT NICO', '2025-07-28 20:28:15', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `operation_manager` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `department`, `supervisor`, `operation_manager`, `email`, `created_at`, `updated_at`, `is_active`) VALUES
(4193, 'CXI11792', 'SALDIVIA, ABRAHAM NEBRES', 'TQAM', 'CXI MNGT', 'Kiko Barrameda', 'Ansaldivia913@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4194, 'CXI11448', 'CERTEZA, NOVELYN LANUZO', 'QA SUP', 'CXI MNGT', 'Abbes Saldivia', 'noviecerteza@hotmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4195, 'CXI12091', 'PAMULAR, ERNISON B', 'QA SUP', 'CXI MNGT', 'Abbes Saldivia', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4196, 'CXI11979', 'GUTIERREZ, BRANDON', 'TRAINING OFFICER', 'CXI MNGT', 'Abbes Saldivia', 'donibadoni17@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4197, 'CXI00525', 'TORRES, JUAN CARLO PABLO', 'SLT', 'CXI MNGT', 'Phay Barrameda', 'juancarlotorres06@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4198, 'CXI00730', 'DUTERTE, RG-BOY CREMAT', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'rgboyduterte23@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4199, 'CXI11647', 'OLAES, ALEXANDER RAY', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'alexander.olaes2002@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4200, 'CXI11652', 'ANGKIKO, MIGUEL JEAN', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'angkikomiguel@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4201, 'CXI11664', 'LOMAT, IVERSON', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'iversonlomat03@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4202, 'CXI11812', 'MONTOYA, CHRISTIAN', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'cian.montoya09@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4203, 'CXI11899', 'GALINATO, NICO', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'nicologalinato80@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4204, 'CXI12100', 'Rolando Moneda Jr.', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4205, 'CXI11383', 'NUEZ, RAINE JASMYN', 'FB / UAT', 'CXI MNGT', 'Charisse Rivera', 'rainenuez@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4206, 'CXI11204', 'PAGUIA, AL CHRISTIAN AMIT', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'alchristian.paguia22@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4207, 'CXI00716', 'DELA CRUZ, LEO ZACHARY', 'MAI - FB', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'zach18delacruz@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4208, 'COM11615', 'RAPIRAP, ANGELICA MONTEJO', 'MAI - FB', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'rapirapindil@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4209, 'COM11744', 'ORIAL, SAMANTHA RAE', 'MAI - FB', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'osamantharae@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4210, 'CXI12059', 'ROSAL, PATRICK ARIAS', 'MAI - FB', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4211, 'COM12066', 'SAMUEL, HANS', 'MAI - FB', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4212, 'CXI00497', 'TAMAYO, AARON', 'MAI - S3', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'potchinyonglahat1321@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4213, 'CXI00823', 'CADSAWAN, JOHANNA MORALES', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'johannacadsawan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4214, 'COM11901', 'ABAD, TRISHA MAE', 'MAI - UAT', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'trishamaeabad185@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4215, 'COM12023', 'COLICO, CARL LOUIS ARCE', 'MAI - UAT', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'cjserrano65@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4216, 'CXI00969', 'QUINAGUTAN, CHINO JEROME ANG', 'MAI - UAT', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'chinojerome1102@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4217, 'COM12022', 'ARIENDA, JOSEPH CARLO VEGA', 'MAI - UAT', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'wonderjosiexcx@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4218, 'COM11902', 'CADAY, CHARLS AERON', 'MAI - UAT', 'NUEZ, RAINE JASMYN', 'Charisse Rivera', 'charlsaeron.caday11@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4219, 'CXI01065', 'MAGNO, CHERISSE', 'MAI - ECAMPUS - DI', 'CXI MNGT', 'Charisse Rivera', 'mrsmagno031715@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4220, 'COM11436', 'ARANDA, JONABEL', 'MAI - ECAMPUS - OB', 'MAGNO, CHERISSE', 'Charisse Rivera', 'arandajonabel1@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4221, 'COM11578', 'SANTOS, LALAINE', 'MAI - ECAMPUS - IVY TECH', 'MAGNO, CHERISSE', 'Charisse Rivera', 'lalamendoza1023@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4222, 'COM11580', 'VITOBINA, ERICA', 'MAI - ECAMPUS - IVY TECH', 'MAGNO, CHERISSE', 'Charisse Rivera', 'ericavitobina@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4223, 'COM12087', 'CULAS, DUFFORD JOHN', 'MAI - ECAMPUS - IVY TECH', 'MAGNO, CHERISSE', 'Charisse Rivera', 'culasduffordjohn@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4224, 'COM12101', 'DELA CRUZ, XYRA', 'MAI - ECAMPUS - IVY TECH', 'MAGNO, CHERISSE', 'Charisse Rivera', 'xyradc13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4225, 'CXI11124', 'DIMAANO, BEA CABOG', 'MAI - ECAMPUS - IVY TECH', 'MAGNO, CHERISSE', 'Charisse Rivera', 'cabogbea@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4226, 'COM12102', 'MOSQUERA, SAM', 'MAI - ECAMPUS - IVY TECH', 'MAGNO, CHERISSE', 'Charisse Rivera', 'sam.mosquera1403@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4227, 'COM11573', 'ABRIGO, DANICA', 'MAI - ECAMPUS - DI', 'MAGNO, CHERISSE', 'Charisse Rivera', 'danicaabrigo13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4228, 'COM12090', 'BERNAL, JOHN IVAN', 'MAI - ECAMPUS - DI', 'MAGNO, CHERISSE', 'Charisse Rivera', 'johnivan.manicio.bernal@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4229, 'COM12088', 'BRAVO, JUSTINE RIENE', 'MAI - ECAMPUS - DI', 'MAGNO, CHERISSE', 'Charisse Rivera', 'Justinerienebravo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4230, 'COM12081', 'NAVARRO, EMMANUEL', 'MAI - ECAMPUS - DI', 'MAGNO, CHERISSE', 'Charisse Rivera', 'emannencia17@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4231, 'COM12089', 'MANALANSAN, GABRIEL', 'MAI - ECAMPUS - DI', 'MAGNO, CHERISSE', 'Charisse Rivera', 'gabrielmanalansan1010@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4232, 'COM11697', 'SALES, ABBYGAIL', 'MAI - ECAMPUS - DI', 'MAGNO, CHERISSE', 'Charisse Rivera', 'gabby.sales0203@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4233, 'CXI01017', 'PAPA, AMEERAH', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', 'papa.ameerah6@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4234, 'CXI11212', 'HASPELA, ROSANNA', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', 'haspelarosanna@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4235, 'COM11577', 'SORIANO, RAMON', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', 'sorianoramonjr@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4236, 'NO_EMP_ID1', 'MAUCANI, MARK', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4237, 'NO_EMP_ID2', 'FORTALEZA, REINER', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4238, 'NO_EMP_ID3', 'ESQUILLO, FRANCIS', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4239, 'NO_EMP_ID4', 'NOLLAS, FRANCIS', 'MAI - ECAMPUS - AA', 'MAGNO, CHERISSE', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4240, 'CXI00006', 'MIRALLES, JEBONNIE ADONIS', 'SOURCING - AIRLINES', 'CXI MNGT', 'Charisse Rivera', 'bhenjtenshi@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4241, 'CXI00279', 'MOPIA, PATRICK BERNARDO', 'GROUPS', 'CXI MNGT', 'Charisse Rivera', 'patrick.b.mopia@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4242, 'CXI00357', 'JAEN, ALBERT DOMENS', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'albertjaen88@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4243, 'CXI00064', 'BASONG, KEITH BRYLLE PANZO', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'keithbryllebasong@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4244, 'CXI00217', 'TORRES, JENESIS', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'jenesis.torres18@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4245, 'CXI00873', 'BARITOGO, CELINE', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'ucellinebaritogo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4246, 'CXI12061', 'SABATER, CARL DUKE PORRAS', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'cdsabater24@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4247, 'CXI12058', 'ESPINOZA, JOSEPH', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'jmannversion1@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4248, 'CXI12063', 'BORGONIA, JOVEN', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'jovenborgonia9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4249, 'CXI00660', 'CUNANAN, JERICO CAPARAS', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'jericocunanan053@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4250, 'CXI00401', 'BERJA, LALAINE VELASCO', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'berjalalaine28@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4251, 'CXI11263', 'ALVARO, ENNOS JARED SAN BUENAVENTURA', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'ejaredalvaro1992@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4252, 'CXI11145', 'BRAZAS, JERIC PAGHUBASAN', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'jericbrazas7@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4253, 'CXI00826', 'MACABUHAY, MAYBEL', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'macabuhaymaybelyn@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4254, 'CXI12064', 'MACARAIG, MARC CARLO', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'marcmacaraig@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4255, 'CXI11525', 'SANTOYO, KATE', 'GROUPS', 'MOPIA, PATRICK', 'Charisse Rivera', 'kate.santoyo.9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4256, 'CXI00370', 'NAPIERE, MINETTE ESPENIDA', 'SINGLES', 'CXI MNGT', 'Charisse Rivera', 'napiereminette040318@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4257, 'CXI00308', 'PICAZO, CHRISTIAN KING BALANQUIT', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'kingpicazo02@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4258, 'CXI00467', 'BAGACAY, ROGELIO BIATO', 'SINGLES', 'CXI MNGT', 'Charisse Rivera', 'bagacayrogie77@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4259, 'CXI00671', 'LIM, GIOVEL ALLEXSANDRA ESPIRITU', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'bactolgiovelallexsandra06@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4260, 'CXI00832', 'ERA, JOHN ANGELO', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'erajohnangelo23@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4261, 'CXI11332', 'UMIPIG, ELVIRA', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'umipigviel@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4262, 'CXI11146', 'KALINISAN, AMOR VILLANUEVA', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'ITSME.MAVKALI@GMAIL.COM', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4263, 'COM11570', 'GALVEZ, EUNICE', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'eunicegalvez24@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4264, 'CXI11337', 'CASTILLO, EUNICE NICHOLE POTES', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'eunicenicholepotes9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4265, 'CXI00425', 'GABRIEL, ROEDEN SINUGBUJAN', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'dengab38@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4266, 'CXI11626', 'DE LOS REYES, ELAINE ORENSE', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'elainedelosreyes29@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4267, 'CXI11877', 'REYES, HIDDY', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'reyeshiddy@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4268, 'CXI11716', 'MAHINAY, APPLE', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'applemahinay22@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4269, 'CXI12008', 'VICENTE, ZERNA', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'perry.ling93@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4270, 'COM11704', 'CORILLO, MARIE ROSS', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'mariecorillo.services@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4271, 'CXI00964', 'OCHEDA, KENDRICK SEAN ALORRO', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'kendrickocheda@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4272, 'CXI11747', 'CALBAYAR, CATHLYN', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'cathlynmaec@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4273, 'COM11837', 'AGUILAR, JOANNA CLAIRE', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'claireaguilar0515@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4274, 'CXI00311', 'ROSALES, MICHELLE PADERON', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'mprworkingemail@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4275, 'CXI11274', 'GARCIA, ASHLEY ANNE DAVEN', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'garciaashleyanne@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4276, 'CXI11202', 'FERNANDEZ, RONESSA AUFEL', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'frnndzrnssa@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4277, 'CXI11725', 'UY, JASPER', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'extremeseverity@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4278, 'CXI11273', 'OCAMPO, KAT', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', '08katocampo05@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4279, 'CXI01089', 'BALIGCOT, EDCEL', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'edcelbaligcot5@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4280, 'CXI11875', 'LOYOLA, ANDREA', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'andrealoyola831@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4281, 'CXI11984', 'FAUSTINO, AIRIEL', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4282, 'CXI12005', 'SAMOY, PAUL VINCENT', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'paulvincent1777@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4283, 'CXI12085', 'BENIGNO, MANJO MANUEL', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'manjomanuelzbenigno@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4284, 'CXI12084', 'ARDUO, MIGUEL FRANCISCO', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'miguelarduo11@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4285, 'CXI12067', 'LIBOT, RICH ANN LOU B', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'richannbacani@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4286, 'CXI12069', 'HANDOG, IRISH B', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'irish_handog@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4287, 'CXI12068', 'ABUDA, ANGELYN P.', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'a.angelynxx@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4288, 'CXI11963', 'SINLAO, BRYLLY', 'SINGLES', 'BAGACAY, ROGELIO', 'Charisse Rivera', 'bryllysinlao28@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4289, 'CXI12007', 'HIPONIA, ANDREW CRUZ', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'drewdrew1380@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4290, 'CXI11903', 'PALARUAN, JOSHUA', 'SINGLES', 'NAPIERE, MINETTE', 'Charisse Rivera', 'jpalaruan2k16@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4291, 'CXI00450', 'VIDOR, EUGENE', 'AA BILLING', 'CXI MNGT', 'Charisse Rivera', 'jinvidor@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4292, 'CXI11330', 'MORADAS, JAYZIN CASTRO', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'moradasjayzin3@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4293, 'CXI11839', 'SAYAMAN, REGGIE', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'sayamanreggie@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4294, 'CXI11848', 'LIMBO, KEVIN', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'larieze@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4295, 'CXI11842', 'AGNER, IRISH MAY', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'irishagnerxx@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4296, 'CXI11845', 'MONINIO, BEJAY', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'moninio.bejay@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4297, 'CXI11850', 'SALAMAT, JO ANN', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'josatgen@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4298, 'CXI12043', 'BORNALES, RYZA EUNIECE', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'bornalesryza@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4299, 'CXI12051', 'ESTOJERO, MARK RAVEN', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'herovenray@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4300, 'CXI12048', 'COSTIBOLO, OLIVIA', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'oliviacostibolo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4301, 'CXI12047', 'CORTEZANO, SHAN CLESTER', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'shanclester123@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4302, 'CXI12054', 'ALONZO, CARLO JOSHUA', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'cjalonzo21@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4303, 'CXI12053', 'URBANES, GRACIEL MAE', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'Gracielu75@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4304, 'CXI12045', 'OPEÑA, AMIR LEGOLAS', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'amiropena2007@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4305, 'CXI12050', 'BALLENAS, PATRICIA ANNE', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'patriciaanneballenas@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4306, 'CXI12041', 'CESA, KEISHIA MAE', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'keishiamaec@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4307, 'CXI12062', 'OSORIO, RODANTE', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'rodanteosorio28@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4308, 'CXI12065', 'SANTOS, LINDSAY', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'santoslindsay23@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4309, 'CXI12001', 'MAHINAY, JAN VAN', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'janvanmahinay0@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4310, 'CXI11990', 'MILLARE, JAKE BAÑEZ', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', 'jakemillare25@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4311, 'CXI12042', 'VARGAS, LEY-ANN', 'AA BILLING DB', 'VIDOR, EUGENE', 'Charisse Rivera', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4312, 'CXI11193', 'FRANCISCO, JONALYN', 'AA BILLING', 'CXI MNGT', 'Charisse Rivera', 'jonalynfrancisco545@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4313, 'CXI11886', 'CENIZAL, ALIASA', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'cenizal335@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4314, 'CXI11887', 'FANGONIL, JERISSA', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'ivonjerissa@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4315, 'CXI11888', 'GONZAGA, HEDDA', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'heddagon15@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4316, 'CXI11889', 'MONTIBON, ANGELICA', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'angelicaorillamontibon@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4317, 'CXI11894', 'TORRES, PRINCESS JASMIAH', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'princessjasmiahtorres@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4318, 'CXI11895', 'RURAL, ALGILENE', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'algirural@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4319, 'CXI11896', 'BEÑEGAS, SOPHIA', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'phiaverdeflor.0814@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4320, 'CXI11844', 'BACAYCAY, CARL AGUSTIN', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'agustincarl86@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4321, 'CXI11892', 'PEDRO, MICHAEL ANGELO', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'pedromichael092723@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4322, 'CXI11891', 'ESCOSA, MARY ROSE', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'mescosa700@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4323, 'CXI11898', 'RAMOS, VERONICA', 'AA BILLING TVL CARD', 'FRANCISCO, JONALYN', 'Charisse Rivera', 'rheenrheen03@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4324, 'CXI11653', 'YENKO, ANNA LIZA BAYSA', 'CREWREZ / REPORTS', 'CXI MNGT', 'Fred Bier', 'abydumaan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4325, 'CXI00160', 'LAZARO, ALLIYAH CAMILLE DIZON', 'CREWREZ / AIR', 'CXI MNGT', 'Fred Bier', 'acdlazaro@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4326, 'CXI00816', 'CHAVES, JEHLIAN ALIJAH BAYOT', 'LEGACY / RECON', 'CXI MNGT', 'Fred Bier', 'jehlianalijahchaves31@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4327, 'CXI11523', 'TIMING, YBRAHIM ORDIALES', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'yyytimmy67@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4328, 'CXI11457', 'BAUTISTA, SHARMAINE NICOLE', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'sharmainenicole.bautista@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4329, 'CXI00780', 'DIESANTA, NONA ALCARAZ', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'diesantanona@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4330, 'CXI11785', 'ZOZOBRADO, AEJAY', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'Zobdaruca28@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4331, 'CXI11517', 'AMORES, KEVIN YANOYAN', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'amoresken75@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4332, 'CXI11281', 'YEE, RENAN PAPA', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'shenyee201710@outlook.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4333, 'CXI00240', 'DELOS REYES, FRANCES CARLA', 'CREWREZ', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'Francesdelosreyesneri@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4334, 'CXI00487', 'GARCIA, MAYUMI TABINGA', 'CREWREZ', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'tgmayumi@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4335, 'CXI11439', 'LOPOZ, JOSE DANIEL', 'CREWREZ', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'dan.lopoz9009@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4336, 'CXI00095', 'ALONZO, KAROLINE LIZAN', 'CREWREZ', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'kharol0928@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4337, 'CXI11883', 'ALTAREJOS, ALEXANDRA MIE', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'alexandramiealtarejos@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4338, 'CXI11693', 'ALABATA, ANGELYN ACUEZA', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'angelyn.alabata02@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4339, 'CXI11455', 'LAZARO, KATRINE CAMILLE', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'katkatlazaro09@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4340, 'CXI00480', 'ESPIRITU, ROWENA CAMINGAO', 'CREWREZ REPORTS', 'YENKO, ANNA LIZA', 'Fred Bier', 'whengespiritu083@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4341, 'CXI01103', 'SALAZAR, ARIEL PEREZ', 'CREWREZ REPORTS', 'YENKO, ANNA LIZA', 'Fred Bier', 'arielsalazar1803@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4342, 'CXI11659', 'REYES, NARICEL', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'naricelcmpsgrdo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4343, 'CXI11742', 'SANTOS, TRIZIA ANNE DALURAYA', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'trizia.santos16@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4344, 'CXI11723', 'IGNACIO, VAN', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'ignvanz123@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4345, 'CXI11797', 'SOLIS, ANDREI CAMUNGOL', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'andreisolis803@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4346, 'CXI11761', 'DIJAMCO, KEIFER JARRETH', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'keiferjarrethdijamco@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4347, 'CXI11461', 'CAPILITAN, KRYSTIL MARIE', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'krystlcpltn@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4348, 'CXI11760', 'ALLEQUIR, ROSALYN', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'rosalyn.allequir@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4349, 'CXI00988', 'CALIMBAHIN, JUSTINE JEANETH DE LOS SANTOS', 'EZY/HAGR TRAINEE', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'justinejeaneth9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4350, 'CXI11682', 'OLAES, RENALD JASPER', 'EZY/HAGR TRAINEE', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'olaesrenaldjasper@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4351, 'CXI11849', 'ORTEGA, JUSTIN HENRY', 'CREWREZ RECON', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'justinhenryortega6@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4352, 'CXI11447', 'CASIPONG, MARK LESTER BERNAL', 'CREWREZ RECON', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'marklestercasipong550@gmail.com', '2025-07-24 01:14:15', '2025-07-24 13:16:59', 1),
(4353, 'CXI11662', 'LACAR, ERCIE', 'CREWREZ RECON', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'ercielacar78@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4354, 'CXI01101', 'GULAYLAY, EZEKIEL ARON GONZALES', 'CREWREZ RECON', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'kieru.5285@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4355, 'CXI11458', 'WATANABE, JOHN CHRISTIAN', 'CREWREZ RECON', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'jcwata13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4356, 'CXI11521', 'BATISLAONG, TRISHA MARIE MORENO', 'LEGACY', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'trishabatislaong10@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4357, 'CXI00028', 'BASILIO, MARCO ANTONIO OLAGUER', 'LEGACY', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'basilio.marco@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4358, 'CXI00142', 'LLAMAS, JUSTINE SARINAS', 'LEGACY', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'reid25520@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4359, 'CXI11826', 'BONZA, WILLY', 'LEGACY', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'willybonza0321@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4360, 'CXI11825', 'SOLSONA, BRENDA', 'LEGACY', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'brendasolsona@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4361, 'CXI00334', 'JIMENEZ, JOSHUA CHUA', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'Keiichi00012@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4362, 'CXI11724', 'MORITE, DARLENE KATE', 'LEGACY', 'CHAVES, JEHLIAN ALIJAH', 'Fred Bier', 'darlenekatemorite10@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4363, 'CXI11555', 'BARBUCO, JHAEROM', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'jerombarbuco123@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4364, 'CXI11722', 'NODADO, VANGIE', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'vangienodado@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4365, 'CXI11719', 'TAY, BELLE JOY', 'EZY/HAGR', 'LAZARO, ALLIYAH CAMILLE', 'Fred Bier', 'bellejoyt@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4366, 'CXI11675', 'ROSETE, KELVIN ABALON', 'BLOCKERS', 'CXI MNGT', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4367, 'CXI00931', 'REYLES, NICOLE ANN KLEIN VALENTOS', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'nicolereyles8@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4368, 'CXI01080', 'DACQUEL, ARTHUR BERNARDEZ', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', '143.yeen@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4369, 'CXI11680', 'DIAZ, NIMFA ANGELA CORPUZ', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'nimfaangelacorpuz@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4370, 'CXI11805', 'VERANO. ERWIN', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'erwinverano00@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4371, 'CXI11807', 'CHOW, CHEWAH EUGENIO', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'chowanthonet@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4372, 'CXI11808', 'MABULAY, ALEXANDRA SALAZAR', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'itsherlexila@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4373, 'CXI11856', 'SORIAO, ANDREA', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'soriaoandrea9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4374, 'CXI11853', 'ABOGADO, JENNYSON', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'abogadojennyson8@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4375, 'CXI11854', 'REMONIDA, MA CRISTINA FLORES', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'mcflores91303@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4376, 'CXI11919', 'BALICAT, GENALYN F.', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'genalynbalicat06@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4377, 'CXI11923', 'GUERRERO, LENARD', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'guerrerolenard081@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4378, 'CXI11921', 'SALAÑO, MARIVIC PALCONIT', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'marivicsalano205@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4379, 'CXI11917', 'VALENCIA, STEPHANIE ANN', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', 'vstephanie0241@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4380, 'CXI12014', 'MELAÑO, LEO', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4381, 'CXI12016', 'SONSONA, VERLYN KAY', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4382, 'CXI12018', 'RABADON, CHARISSE', 'BK-US', 'ROSETE, KELVIN', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4383, 'CXI12012', 'ROSALES, ROMMEL', 'BK-APAC', 'ROSETE, KELVIN', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4384, 'CXI11802', 'AMADOR, AURABELLE A.', 'BK-APAC', 'ROSETE, KELVIN', 'Fred Bier', 'TBA', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4385, 'CXI11446', 'INOCENCIO, LYNIEL ROSE', 'BK-APAC', 'ROSETE, KELVIN', 'Fred Bier', 'Inocenciorose014@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4386, 'CXI11291', 'LEYESA, ROSEMARIE BRAZIL', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'rosemarieleyesa@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4387, 'CXI11287', 'JAURIGUE, AILEEN ABRATIQUE', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'jannsie25@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4388, 'CXI11811', 'CAPARAS, ARLENE JOCSON', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'arlenejcaparas@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4389, 'CXI00712', 'BAILLO, CRISTINE QUIRANTE', 'BLOCKERS', 'CXI MNGT', 'Fred Bier', 'Cristinebaillo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4390, 'CXI11649', 'FADRI, ERICKA FHEA FETALINO', 'BK-US', 'BAILLO, CRISTINE', 'Fred Bier', 'erickafheafadri@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4391, 'CXI11250', 'LLANERA, OLIVIA', 'BK-US', 'BAILLO, CRISTINE', 'Fred Bier', 'llaneraolive@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4392, 'CXI11800', 'ESTACIO, CYREL JANE CELESTE', 'BK-US', 'BAILLO, CRISTINE', 'Fred Bier', 'cyreljaneestacio23@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4393, 'CXI00935', 'CORPUZ, MARITES BERNAL', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'thekla23corpuz@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4394, 'CXI11684', 'BELOSTRINO, JEROME PAGUYO', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'Jerome.Belostrino@corpay.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4395, 'CXI11855', 'DUMAAN, LEILA KYLA', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'Kyladumaan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4396, 'CXI11978', 'MATEO, CRIZTINE', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'criztinemateo08@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4397, 'CXI12013', 'ANULACION, IVY', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'ivyanulacion337@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4398, 'CXI11977', 'SANCHEZ, ANGELYN', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'angelynsanchez826@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4399, 'CXI11922', 'MAGLAWAY, NIEL JOHN', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'nieljohnmaglaway02@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4400, 'CXI11920', 'ESCALLENTE, MICKO JAY', 'BK-EMEA', 'BAILLO, CRISTINE', 'Fred Bier', 'emickojay@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4401, 'CXI11801', 'GOZUN, CHONA', 'BK-APAC', 'BAILLO, CRISTINE', 'Fred Bier', 'chonagozun@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4402, 'CXI11918', 'AREGLADO. SHAWNN ERIC', 'BK-APAC', 'BAILLO, CRISTINE', 'Fred Bier', 'mast3rkata@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4403, 'CXI00781', 'AMIL, IRENE CAPARAS', 'BK-APAC', 'BAILLO, CRISTINE', 'Fred Bier', 'nathaliasagut@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4404, 'CXI00976', 'SABA, ALMA ELIPE', 'BK-APAC', 'BAILLO, CRISTINE', 'Fred Bier', 'ramosella616@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4405, 'CXI11260', 'DEOQUINO, DOREN GRACE MEDROCILLO', 'BK-APAC', 'BAILLO, CRISTINE', 'Fred Bier', 'dorengracedeoquino525@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4406, 'CXI00922', 'NACIONAL, REIGNALYN RUBIO', 'BLOCKERS', 'CXI MNGT', 'Fred Bier', 'reignalynnacional@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4407, 'CXI00748', 'MORGADO , RONNA MAE EDO', 'BK-US', 'NACIONAL, REIGNALYN', 'Fred Bier', 'morgadoronna@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4408, 'CXI11632', 'ESGUERRA, CRIZALYN ESPIDILLION', 'BK-US', 'NACIONAL, REIGNALYN', 'Fred Bier', 'esguerracrizalyn05@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4409, 'CXI11798', 'RAVELO, MARY JOY', 'BK-US', 'NACIONAL, REIGNALYN', 'Fred Bier', 'maryjoyravelo25@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4410, 'CXI11809', 'ROXAS, ALQUIN JOHN GERMINO', 'BK-US', 'NACIONAL, REIGNALYN', 'Fred Bier', 'alquinjohn08@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4411, 'CXI12015', 'CALALANG, ZAIRA JANE', 'BK-US', 'NACIONAL, REIGNALYN', 'Fred Bier', 'zairacalalang@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4412, 'CXI12011', 'NUYDA, JANNA', 'BK-US', 'NACIONAL, REIGNALYN', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4413, 'CXI00981', 'GABANTO, GLACEL NOROÑA', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'glacelg24@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4414, 'CXI11651', 'AGUINALDO, CAREN', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'aguinaldocaren9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4415, 'COM11703', 'LANZA, LAIZA NUEVO', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'Laizalanza18@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4416, 'CXI11780', 'CRISOSTOMO, DENNIS EARL', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'dedcrisostomo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4417, 'CXI11974', 'LIRAZAN, ROXANE JOY', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'lirazanroxane@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4418, 'CXI12017', 'VILLEGAS, ERICA', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4419, 'CXI11973', 'RAMOS, MICHAEL JHON', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'jhayr8339@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4420, 'CXI11803', 'GAYAGAYA,CHARIE VIC REY', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'crie446@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4421, 'CXI11258', 'RUBI, MYLYN CAROLLA', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'rubimylyn@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4422, 'CXI11972', 'LIMBO, MARIONE', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'marionelimbo09032000@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4423, 'CXI00987', 'ROBLES, ERICA LAINE CAMPUSPOS', 'BK-EMEA', 'ROSETE, KELVIN', 'Fred Bier', 'ericalainerobles30@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4424, 'CXI11975', 'FLORES, JENALYN', 'BK-EMEA', 'NACIONAL, REIGNALYN', 'Fred Bier', 'jhennaflores8@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4425, 'CXI00361', 'CONCEPCION, CHERRY MAY CATEDRILLA', 'AMERICAN AIRLINES', 'CXI MNGT', 'Benedict Mendoza', 'concepcioncherrymay@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4426, 'CXI11590', 'LATORENO, ABEJUN LASALA', 'AMERICAN AIRLINES', 'CXI MNGT', 'Benedict Mendoza', 'latoreno19@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4427, 'CXI01038', 'VIDA, RAVIEL ERNEST LOPEZ', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'ravielvida1@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4428, 'CXI11504', 'SERRANO, DEVINE GRACE LIMBO', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'devineserrano9@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4429, 'CXI11656', 'LOGAN, MIKA ELLA DENIS', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'mikaellalogan149@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4430, 'CXI11597', 'MAESTRE, SIDNEY MANALANG', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'maestresidney8@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4431, 'CXI11755', 'ANZANO, VERONNE', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'veronneanzano14@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4432, 'CXI11916', 'CATANGUI, ANDREFF DENS GANZON', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', '08deng@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4433, 'CXI11913', 'CALINGA, KARL RONQUILLO', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'karlcalinga09@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4434, 'CXI11915', 'EDER, DANIELLE HILLARY BERNARDINO', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'danielleeder661@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4435, 'CXI11910', 'DELIZO, ANTHONY PITPIT', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'thondelizo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4436, 'COM11705', 'GARCIA, JIAN ANGELO', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'bim.yellowwarbler@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4437, 'COM11700', 'SAGUN, KEANA ISABELLE', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'keanaisabelle13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4438, 'CXI11607', 'MAJILLO, MAEZON VALES', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'maezonmajillo599@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4439, 'CXI11503', 'MATEO, ANA CHARISSE TOME', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'acmateooo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4440, 'CXI11583', 'CORLIT, RISA CASTELLANO', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'raisacastell46@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4441, 'CXI11912', 'MARFIL, JERICAH MAE COLOCADO', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'jericamarfil24@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4442, 'CXI11911', 'DOMINGUEZ, RONEL', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'Dominguezronel1629@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4443, 'CXI11589', 'CENTINO, ALYSSA SYRIL', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'As.centino.07@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4444, 'CXI11417', 'ABAN, SHYNA LAINE', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'shynalaineaban@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4445, 'CXI11907', 'TAPALLA, ANGELA MONTON', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'tapallaangela04@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4446, 'CXI11502', 'CUNANAN, JO ANDREA SABENIA', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'joandreacunanan@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4447, 'CXI11657', 'PEREGRINO, FATIMA', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'allennamacabacyao@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4448, 'CXI11595', 'SANCHEZ, ABIGAIL BUENCONSEJO', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'abiece076@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4449, 'CXI11494', 'CALINOG, JEFFREY', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'jepoycalinog03@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4450, 'CXI11757', 'LOVEDORIAL, RENE', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'lovedorial051998@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4451, 'CXI11603', 'DISTOR, YUSHA MAICO', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'yushamdistor@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4452, 'CXI11905', 'DUHAYLUNGSOD, CLIFFORD KID DAVIS', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'Clifford.duhaylungsod14@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4453, 'CXI11909', 'ORO, LEDYVEI PANIZA', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'Ledyveio@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4454, 'CXI11914', 'LEGASPI, KYLA MAE', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'kylalegaspi010@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4455, 'CXI11906', 'FLORES, ALONICA MAE QUINSAY', 'AMERICAN AIRLINES', 'CONCEPCION, CHERRY MAY', 'Benedict Mendoza', 'nicaquinsay@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4456, 'CXI11506', 'MAYOR, MARK ANDREI ARAGON', 'AMERICAN AIRLINES', 'LATORENO, ABEJUN', 'Benedict Mendoza', 'markandreiaragonmayor@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4457, 'CXI00347', 'GALLENO, GENREV ZION BOLO', 'APAC', 'CXI MNGT', 'Benedict Mendoza', 'genrevgalleno@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4458, 'CXI11183', 'PRIMOR, JOANA ROSE SIDAMON', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'primorjoanarose@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4459, 'CXI00517', 'TILA, APRIL KHRISTAL', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'apriltila16@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4460, 'CXI11622', 'DELOS SANTOS, ABIGAIL JAN G.', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'abigailjan.delossantos@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4461, 'CXI11968', 'NACARIO, ELVINA JUNNEL', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'elvinajunnel.nacario@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4462, 'CXI11231', 'MUSA, ALFRED TIMOTHY CAPON', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'amusamcmxcii@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4463, 'CXI00149', 'BARRAMEDA, ERIKA MAE BORBO', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'ekabarrameda080101@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4464, 'CXI00711', 'GOMEZ, JOSEPH DE MESA', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'gomezprince14@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4465, 'CXI00869', 'BASELONIA, EUNEIL GELLE', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'baseloniaeuneil@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4466, 'CXI11623', 'VILLANUEVA, CATHERINE MASING', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'dannicalourise30@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4467, 'CXI11970', 'BALAUZA, KEN DARYL', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'kendarylalfantebalauza@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4468, 'CXI11969', 'LAGARDE, JOANNA', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'itsannalagarde@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4469, 'CXI00904', 'ESCALANTE, NIMWHEY ALAGAR', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'escalante.nimwhey090402@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4470, 'CXI11971', 'NERA, ABELL JON', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'danllory001@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4471, 'CXI11344', 'MAESTRE, EMMANUEL JOHN SANTULAN', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'maestreemmanueljohn@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4472, 'CXI01015', 'GERONA, JOHN JOSHUA CASTRO', 'APAC', 'GALLENO, GENREV ZION', 'Benedict Mendoza', 'johnjoshuagerona@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4473, 'CXI11233', 'PONTAWE, ALVIN', 'US CREW', 'CXI MNGT', 'Benedict Mendoza', 'alvin.pontawe0220@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4474, 'CXI11219', 'PIDOT, LAWRENCE TABALNO', 'QA SPECIALIST', 'PAMULAR, ERNISON', 'Abbes Saldivia', 'lawrencepidot11@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4475, 'CXI11658', 'DOYOGAN, JUNICHI NEIL', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'junichineil@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4476, 'CXI00813', 'CASTILLO, ANDREI JAN CYRUS RICASATA', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'castillocyrus006@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4477, 'CXI11641', 'VIZCARRA, MARK AZRIEL', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'markazrielvizcarra@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4478, 'CXI11306', 'RIMANDO, MELANIE UNGSOD', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'melanie.rimando@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4479, 'CXI11677', 'VENTOSA, REINIEL', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'nielventosa48@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4480, 'CXI11548', 'BONIFE, JIN EZEKIEL', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'bonifejin@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4481, 'CXI11965', 'RUBI, BRIAN DAVE', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'rubibrian200@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4482, 'CXI11678', 'LOVENDINO JR, ARTHUR', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'arthur.lovendinojr@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4483, 'CXI00252', 'JOCOM, IVAN JOSHUA CAMANO', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'camanoivanjoshua@gmail.com', '2025-07-24 01:14:15', '2025-07-25 02:02:16', 1),
(4484, 'CXI00060', 'VISITACION, CHRISTINE', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'tin2visitacion01@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4485, 'CXI11640', 'BERIN, KHEN KHURLO D.', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'khenkhurlo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1);
INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `department`, `supervisor`, `operation_manager`, `email`, `created_at`, `updated_at`, `is_active`) VALUES
(4486, 'CXI00958', 'PAN, KRISTINE NICA PILLA', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'kristinenicapan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4487, 'CXI11981', 'DE LARA, ANICON', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'nokinasenju@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4488, 'CXI11982', 'FUEGO, JEMAIMA', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'seikajemaima@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4489, 'CXI11307', 'MONLEON, ARLENE JINAO', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'arlenemonleon080486@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4490, 'CXI00261', 'COLICO, CARL LOUIS ARCE', 'US CREW', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'carllouis.colico@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4491, 'CXI00701', 'NICDAO JR., ROGELIO ORIOL', 'CANCEL QUEUE', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'nicdaorogelio161908@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4492, 'CXI00820', 'EVIA, DE JESUS MELODIE', 'CANCEL QUEUE', 'PONTAWE, ALVIN', 'Benedict Mendoza', 'melodieevia@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4493, 'CXI00045', 'PARIL, MARWIN ARGA', 'CLC EMERGENCY', 'CXI MNGT', 'Benedict Mendoza', 'parilmarwin1998@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4494, 'CXI11142', 'CALMA, RUSSEL JAY CRUZ', 'CLC EMERGENCY', 'CXI MNGT', 'Benedict Mendoza', 'russeljaycalma@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4495, 'CXI00020', 'ARANZANSO, BESSIE MENDOZA', 'CLC EMERGENCY', 'CXI MNGT', 'Benedict Mendoza', 'bessie.mendoza21@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4496, 'CXI11188', 'CARALDE, KENNY ROBERT DELA CRUZ', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'caralde.kennyr@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4497, 'CXI11940', 'ALIPIO, PAUL JOHN', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'alipiopauljohn980@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4498, 'CXI11450', 'GALANG, JOHN', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'johnchuagalang21@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4499, 'CXI11942', 'CAPILLAN, STEPHANIE', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'capillansteph@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4500, 'CXI11254', 'SUSTITUIDO, BERNARD JOE SALVATIERRA', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'bernardjoesustituido@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4501, 'CXI11256', 'NEPOMUCENO, AJ CARL REBANCOS', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'nepomucenoajcarl@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4502, 'CXI00769', 'ROSALES, BERNADETTE', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'bernadetteprosales12@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4503, 'CXI11753', 'ABAN, ZACHARY', 'CLC - BILLING', 'PARIL, MARWIN', 'Benedict Mendoza', 'zachary.aban@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4504, 'CXI11759', 'MARAMOT, ROSEMARIE', 'CLC - RESERVATIONS', 'PARIL, MARWIN', 'Benedict Mendoza', 'rosemariemaramot24@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4505, 'CXI00790', 'SAMBERI, DANILO ALBISO', 'CLC - RESERVATIONS', 'PARIL, MARWIN', 'Benedict Mendoza', 'danilosamberi2002@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4506, 'CXI11585', 'SOROSORO, ELDWIN HERRADURA', 'CLC - RESERVATIONS', 'PARIL, MARWIN', 'Benedict Mendoza', 'sorosoroeldwin05@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4507, 'CXI12021', 'CAMUA, ERICO', 'CLC - RESERVATIONS', 'PARIL, MARWIN', 'Benedict Mendoza', 'ericocamua45@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4508, 'CXI11980', 'GERONIMO, RACHELLE IRIS', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'mrsringc05092021@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4509, 'CXI11588', 'RICALDE, MARK BRIAN JOSHUA QUERUBIN', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'Work.MarkRicalde@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4510, 'CXI11726', 'PASCUA, SUSANA D', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'relationship.status.asus@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4511, 'CXI01034', 'CABALLES, GLENN KRYZTOFER PEREZ', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'kryztofer02@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4512, 'CXI00868', 'BAGOBE, KIM', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'kimbagobe40@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4513, 'CXI12019', 'LACBAY, JOHN CARLO', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'carlo.lacbay12@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4514, 'CXI11593', 'TEJADA, JHON DEXTER TAMALA', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'jd.tejada03@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4515, 'CXI00284', 'RIVAS, JEREMIAH ANGEL', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'jeje.napalan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4516, 'CXI12020', 'RIVAS, JOHANN CARL', 'CLC - RESERVATIONS', 'CALMA, RUSSEL JAY', 'Benedict Mendoza', 'johannnapalan0@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4517, 'CXI00014', 'AGUIBITIN, ERVIN JOHN REYES', 'HOTELS', 'CXI MNGT', 'Benedict Mendoza', 'ervinjohnaguibitin018@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4518, 'CXI11303', 'MARILLA, MARY ANN ADVINCULA', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'maryannmrll@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4519, 'HOTELS 1', 'GAYOL, CHRISTIAN', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4520, 'HOTELS 2', 'REAS, PAOLO GERARD CANETE', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4521, 'HOTELS 3', 'OLLERO, EARL CEDRIC DAVAL-SANTOS', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4522, 'CXI11999', 'BATILO, MA. ALTHEA', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'mariaaltheabatilo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4523, 'CXI12000', 'SABATER, KYLE ALBERT', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'sabaterkyle@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4524, 'CXI11879', 'JAMORA, ERICA', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'jamora.erica19@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4525, 'CXI11880', 'LOM-OC, CHRISTIAN GEROM', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'cristiangeromlomoc@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4526, 'CXI11288', 'CARDENAS, ANGELICA DE LARNA', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'crdnsacilegna@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4527, 'CXI11507', 'CABALLES, CHRISTIAN NECANOR', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'chancaballes1590@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4528, 'CXI11654', 'BASALO, ANDRIAN', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'macao.bringas@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4529, 'CXI11153', 'MASANGKAY, ARIES LIE RAMIREZ', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'lilagad13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4530, 'CXI00923', 'FERNANDEZ, MARY ANN RAMOS', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'jayrann76@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4531, 'CXI00921', 'TOCMO, RAYMAN COLD IZED PENALOSA', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'rctocmo33@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4532, 'CXI00367', 'REGALA, MARIA KRISTINA RITO', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'kristinaregala1@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4533, 'CXI00920', 'REYES, BOY', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'boy.baybay1991@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4534, 'CXI00919', 'CONCIO, JENNIE LYN PATRICIO', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'jenpatconcio@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4535, 'CXI00822', 'SIDOCON, PRINCESS TORDEL', 'HOTELS', 'AGUIBITIN, ERVIN JOHN', 'Benedict Mendoza', 'sidoconprsid@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4536, 'CXI01030', 'SANCHEZ, ALTHEA MABEL BUENCONSEJO', 'ALE ADMIN', 'CXI MNGT', 'Benedict Mendoza', 'altheamabelsanchez@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4537, 'CXI01039', 'VALENCIA, MARTIN RAY MIRANDA', 'ALE ADMIN', 'CXI MNGT', 'Benedict Mendoza', 'martinray.valencia@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4538, 'ADMIN_1', 'PANGANIBAN, MARK GERALD SORIANO', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'panganibanmgerald@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4539, 'ADMIN_2', 'HAGONG, MERYLL V.', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'hagongyenna01@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4540, 'CXI11126', 'GARCIA, ROYCE ANN', 'CLAIMS', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'royceannegarcia@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4541, 'CXI11444', 'DELA CRUZ, DARYLL MUÑOZ', 'CLAIMS', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'Darylldelacruz634@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4542, 'CXI11857', 'INOVEJAS, CHRISTIAN JAKE', 'CLAIMS', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'jake24inovejas@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4543, 'CXI00932', 'ORFINADA, JUNEL VILLAFLOR', 'SSB / PS', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'jnlorfinada@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4544, 'CXI11864', 'MONTINOLA, JULIE MAE', 'SSB / PS', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'Julie.montinola97@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4545, 'CXI11709', 'DELA CRUZ, KHNEYZEL KISS DENIEL', 'SSB / PS', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'khneyzelkissdelacruz@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4546, 'CXI01033', 'RULLODA, JEANKY', 'SSB / PS', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'jeanrulloda15@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4547, 'CXI11624', 'SAMOT, FROILAN M.', 'SSB / PS', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'samotfroilan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4548, 'CXI11859', 'DIMALANTA, MARIANNE', 'SSB / PS', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'ainahdimalanta02@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4549, 'CXI11625', 'DAROY, LORENA', 'TM / FR', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'july4lorena@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4550, 'CXI11865', 'LECHIDO, JULIANA MIKYLLA', 'TM / FR', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'julianamikyllalechido@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4551, 'CXI00313', 'GONGOB, PHOEBE JEAN CHIU', 'TM / FR', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'gongobphoebejean@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4552, 'CXI00173', 'MANIMTIM, ADRIANE RASE', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'adrianmanimtim@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4553, 'CXI11863', 'PORCEL, JOSE MANUEL', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'porcelmanuel0@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4554, 'CXI11858', 'BALSOMO, MARK DANIELLE', 'ADMIN', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'balsomomarkdanielle@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4555, 'CXI11991', 'BECHACHINO, ANGELICA LOUIS', 'ADMIN', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'loubchn@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4556, 'CXI11937', 'CORPORAL, JONAS', 'ADMIN', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'jonascorporal30@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4557, 'CXI11861', 'DURAN, MARK JEVIN', 'ADMIN', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'markjevinduran33@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4558, 'CXI11860', 'PACULBA, JEFFREY', 'ADMIN', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'jeffreypaculba234@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4559, 'CXI11679', 'PAMINIANO, DOLORES MARIE', 'ADMIN', 'SANCHEZ, ALTHEA MABEL', 'Benedict Mendoza', 'elijahmaria03@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4560, 'CXI11733', 'CEDRON, JOHN ANTHONY', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'cedronja@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4561, 'CXI11936', 'VOLANTE, MARK JONEL', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', 'volantemark0004@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4562, 'ADMIN_3', 'ALVAREZ, EDRICK MIOLE', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4563, 'ADMIN_4', 'DE LEON, KRIZZIA MAE BINCAL', 'ADMIN', 'VALENCIA, MARTIN RAY', 'Benedict Mendoza', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4564, 'CXI00818', 'SAPNO, MARIE LINA', 'FOLIO CHASERS', 'CXI MNGT', 'Christopher Paller', 'marielinasapno@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4565, 'CXI00699', 'BAILLO, MARY HAIL QUIRANTE', 'FOLIO CHASERS', 'CXI MNGT', 'Christopher Paller', 'baillomhae@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4566, 'CXI11385', 'NEPOMUCENO, ARVIN AVILA', 'FOLIO CHASERS', 'CXI MNGT', 'Christopher Paller', 'arvinnepomuceno567@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4567, 'CXI11804', 'CABASAG JERICO BIONO', 'ATLAS BILLING', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'cabasagjerico7@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4568, 'CXI11443', 'QUIBOQUIBO, YESHA LAMONTE', 'ATLAS BILLING', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'yeshaquiboquibo3@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4569, 'CXI11799', 'LADRERA, RHENDIO', 'ATLAS BILLING', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'rhendioladrera3@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4570, 'CXI11829', 'VILLARDAR, RIZA MAE', 'ATLAS BILLING', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'villardarrizab@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4571, 'CXI11827', 'CALIMBAHIN, TRIXIA', 'ATLAS BILLING', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'xiacalimbahin@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4572, 'CXI11309', 'CALMA, OLIVIA CHING', 'ATLAS BILLING', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'xandra.ching@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4573, 'CXI11831', 'BENAVIDEZ, RESTIELHYN OHVEL', 'TA', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'restielhynb@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4574, 'CXI11638', 'GONZALES, KRYSSNETTE RAZLE F.', 'TA', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'kryssnetterg@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4575, 'CXI11613', 'SANTIAGO, JOSHUA', 'TA', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'santiagojoshua001@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4576, 'CXI01047', 'BISAIN, DAISYREI AGANINTA', 'TA', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'bisaindaisyreii@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4577, 'CXI11992', 'VALDEZ, JOHN PAUL CASTILLA', 'TA', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'valconfi1212@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4578, 'CXI00387', 'DELA CURA, IVAN JONES MENTOY', 'BILL TRACKING (Lisa Brooks)', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'IDELACURA@GMAIL.COM', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4579, 'CXI01043', 'BAUTISTA, CHASTINE AMOR BINADAY', 'BILL TRACKING (Lisa Brooks)', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'chastine.bautista@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4580, 'CXI00551', 'BANTUGAN, ARLEEN CRISOSTOMO', 'BILL TRACKING (Troy Bowman)', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'bantugan88@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4581, 'CXI11534', 'MUGAS, KARIN', 'BILL TRACKING (Troy Bowman)', 'NEPOMUCENO, ARVIN', 'Christopher Paller', 'karindmugas@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4582, 'CXI11832', 'SANTIAGO, SEAN KHRYZZ', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'seankhryzz.santiago@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4583, 'CXI11833', 'MALANG, DHODIE', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'dhodiemalang0129@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4584, 'CXI11479', 'ABELEDA, JONH LEWIS', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'jonhlewisabeleda2121@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4585, 'CXI11431', 'BRILLO, ALEXIS JOHN', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'alexisjohnbrillo29@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4586, 'CXI11400', 'NAMUCO, JOHN CARLOS', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'Namuco60@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4587, 'CXI00846', 'SALVACION, EMMANUEL', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'emmansalvacion@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4588, 'CXI00878', 'LEGASPI,MICAH RUTH', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'micahruthlegaspi14@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4589, 'CXI11314', 'CARBONILLA, APRHYL CAMILLE HIDALGO', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'camillecarbonilla72@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4590, 'CXI11934', 'MAYOR, MARK AARON ARAGON', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'mayormarkaaron@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4591, 'CXI12028', 'SABORDO, JELLY', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'jelsabordo01@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4592, 'CXI12037', 'URSAL, MARK RJ', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'markrjursalornopia@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4593, 'CXI11621', 'CALOSTE, BERNADETH M.', 'DIRECT FOLIO', 'BAILLO, MARY HAIL', 'Christopher Paller', 'calostebernadeth@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4594, 'CXI11781', 'GIRONELLA, ALECKYLE CHESTER', 'DIRECT FOLIO', 'BAILLO, MARY HAIL', 'Christopher Paller', 'ckgironella@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4595, 'CXI11822', 'AVILES, REYAMI L.', 'DIRECT FOLIO', 'BAILLO, MARY HAIL', 'Christopher Paller', 'reyamiaviles21@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4596, 'CXI11796', 'TAGLE, JOAN SAQUILAYAN', 'DIRECT FOLIO', 'BAILLO, MARY HAIL', 'Christopher Paller', '4workjoan@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4597, 'CXI11714', 'PONCIANO, KEANA CIELO', 'ALE', 'BAILLO, MARY HAIL', 'Christopher Paller', 'keanaponciano@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4598, 'CXI00529', 'ICALIA, EZEKIEL', 'BNSF', 'CXI MNGT', 'Christopher Paller', 'icaliaezekiel@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4599, 'CXI11421', 'SEVILLA, ANN CLAUDETTE', 'AUTOBOOKING', 'CXI MNGT', 'Christopher Paller', 'anneclaudettesevilla@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4600, 'CXI01095', 'ABILES, CHARLZ DANEVER GUIBIJAR', 'AUTOBOOKING', 'CXI MNGT', 'Christopher Paller', 'charlzdanever.abiles@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4601, 'CXI11180', 'PACSON, ARYANNA', 'BNSF', 'CXI MNGT', 'Christopher Paller', 'lostmykeysagain01@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4602, 'CXI12060', 'CORRAL, GREGON', 'AUTOBOOKING', 'CXI MNGT', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4603, 'CXI12057', 'CRUZ, KIM CARLO', 'AUTOBOOKING', 'CXI MNGT', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4604, 'CXI11483', 'AREVALO PO, MARK FRANCES', 'AUTOBOOKING', 'CXI MNGT', 'Christopher Paller', 'markarevalopo@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4605, 'CXI00862', 'SOROSORO, EDNETH JOHN HERRADURA', 'BNSF', 'CXI MNGT', 'Christopher Paller', 'edneth.1996@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4606, 'CXI01053', 'FULLO, VERONICA SAMONTEZA', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'veronicafayefullo@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4607, 'CXI00899', 'RODRIGUEZ ALPHA BLESS', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'alpha.reenar16@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4608, 'CXI12077', 'SICO, GRACE NICOLE D.', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4609, 'CXI12078', 'BOLANTE, ALYSSA JANE', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4610, 'CXI00690', 'INDONG, EVELYN', 'BNSF - ON', 'ICALIA, EZEKIEL', 'Christopher Paller', 'evelynlianza022103@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4611, 'CXI00691', 'MASIGLA, ROSSFEL', 'BNSF - ON', 'ICALIA, EZEKIEL', 'Christopher Paller', 'masiglaofhel3@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4612, 'CXI11407', 'PINO, RACHELLE', 'BNSF - ON', 'ICALIA, EZEKIEL', 'Christopher Paller', 'rachellepino213@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4613, 'CXI11998', 'MADERA, ANGELO', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', 'angelo.madera13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4614, 'CXI12003', 'ESCOSA, JEANY', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', 'soheilaflare@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4615, 'CXI11873', 'MANALO, JAN DARREN', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4616, 'CXI11784', 'BULASAG, SHIENA CODICO', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4617, 'CXI11821', 'DILOY, AMORSOLO R.', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', 'ardiloy122187@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4618, 'CXI11618', 'AMBOS, MARK JOSUA D', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', 'mjambos10@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4619, 'CXI11871', 'SATO, PRINCE ALPHARD', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4620, 'CXI11820', 'LACSON, ADRIAN P.', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', 'adrianlacson011@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4621, 'CXI11672', 'PEÑALBA, MARK KEVIN', 'BNSF CALLBACK TEAM', 'ICALIA, EZEKIEL', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4622, 'CXI11217', 'MIRANDA, IMELDA MONTEGREJO', 'BNSF CALLBACK TEAM', 'ICALIA, EZEKIEL', 'Christopher Paller', 'mirandaimelda53@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4623, 'CXI12070', 'CASPILLO, JOHNEL EBENEZER', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4624, 'CXI12076', 'VOLUNTAD, GIAN CARLO G', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4625, 'CXI12080', 'SIGUENZA, MELODIE BACALLA', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4626, 'CXI11552', 'MANALO, FERDIE', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'ferdzkte@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4627, 'CXI11989', 'SORIANO, ZYRA NICOLE', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'zyra.soriano.007@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4628, 'CXI12036', 'MENDOZA, RANDELL JAMES', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'TBA', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4629, 'CXI11782', 'VELASCO, CLARICE ANN', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'vclariceann@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4630, 'CXI12026', 'MARCIAL, PRINCE NATHANIEL', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'TBA', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4631, 'CXI11927', 'ARAGON, RAY ANGELO', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'rayangeloaragon@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4632, 'CXI11988', 'ALFONSO, JAYLIN', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'jaylinalfonso311@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4633, 'CXI11997', 'ORQUIZA, EULA MAXENE', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'orquizaeula@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4634, 'CXI11783', 'BAYONETO, JOHN PATRICK, G.', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'johnpatrickbayoneto21@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4635, 'CXI11488', 'RODRIGUEZ, MARIEL', 'BNSF', 'PACSON, ARYANNA', 'Christopher Paller', 'FIONAYOUNGSAMSON@GMAIL.COM', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4636, 'CXI11686', 'MARTIN, KURT ADRIEN PAUL', 'BNSF CALLBACK TEAM', 'PACSON, ARYANNA', 'Christopher Paller', 'az09garrix@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4637, 'CXI11777', 'AMODIA, JASMIN BELLE', 'BNSF CALLBACK TEAM', 'PACSON, ARYANNA', 'Christopher Paller', 'jasminamodia2001@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4638, 'CXI12083', 'CRUZ, RALPH MICHAEL MALABANAN', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', '', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4639, 'CXI11926', 'PERUCHO, EMILYN', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'maemilynperucho214@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4640, 'CXI11925', 'GARCIA, PRINCESS LANA', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'garciaprincessiana@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4641, 'CXI11819', 'EUGENIO, PAULA JANE S.', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'janeeugenio00014@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4642, 'CXI12032', 'MAYORES, CHRIS RAVEN', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'TBA', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4643, 'CXI11928', 'LAVIÑA, NOLI', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'nolireyeslavina@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4644, 'CXI11551', 'PANGANIBAN, CHRISTIAN DAVEN', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'panganibandabs79@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4645, 'CXI11751', 'ROSARIO, NELTON JOHN', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'Neltonjhonr@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4646, 'CXI00912', 'GRABOL, JOAN QUINTO', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'initialjade@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4647, 'CXI00889', 'ARGOSO, ALFREDO ODRONIA', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'alfredoaoso01@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4648, 'CXI11674', 'SORTIJAS, AJ', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'ajsortijas1321@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4649, 'CXI11484', 'GERILLA, JASPER', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'gerillajasper22@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4650, 'CXI11173', 'VILLAMARZO, MARK GIL', 'BNSF', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'markgilvillamarzo23@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4651, 'CXI11248', 'ARGUELLES, GARRY SANTOS', 'BNSF - ON', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'garrysantosarguelles@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4652, 'CXI11198', 'SERRANO, ALDRICH MATHEW LAGAMON', 'BNSF - ON', 'SOROSORO, EDNETH JOHN', 'Christopher Paller', 'aldrichmathewserrano@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4653, 'CXI12079', 'LEGASPI, RAQUEL', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4654, 'CXI12082', 'CANDELARIO, CLEAR AUBREY LABIAL', 'BNSF', 'ICALIA, EZEKIEL', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4655, 'CXI11323', 'LAPIDARIO, JOHN REXON', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'lapidariorexon@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4656, 'CXI11985', 'CREENCIA, DONAVHEE', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'cdonavhee@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4657, 'CXI11933', 'AGARPAO, ROVEA', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'beaagarpao95@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4658, 'CXI11133', 'JAVIER, JHEM KRIZZIA KEMPIS', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'jhemjavier17@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4659, 'CXI11870', 'BOBADILLA, DARVIN JULIUS', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'darvinbobadillay11@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4660, 'CXI11814', 'CONCEPCION, CHEYENNE LARISSA S.', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'cheylarissa13@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4661, 'CXI11695', 'GONZALES, GRACEMINDA, G.', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'grace.gonzales322@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4662, 'CXI00835', 'RAMOS, EHRICA CARIÑO', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'ramosehrica286@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4663, 'CXI11363', 'SALVADOR, ANGEL', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'salvador06angel@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4664, 'CXI11302', 'DIAZ, ANGELICA JOY', 'AB CALLBACK TEAM', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'diazangelicajoy007@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4665, 'CXI11236', 'DE GUZMAN, NICKI ANN', 'AB CALLBACK TEAM', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', 'nickianndeguzman00@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4666, 'CXI12075', 'CORPUZ, AKINTO L.', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4667, 'CXI11539', 'BUSIO, JANNA MAE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'busiojannamae@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4668, 'CXI11536', 'MABALAY, JEAN ARIELLE A', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'jnrllmbly.09@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4669, 'CXI11533', 'PATACSIL, MARIEL CHRISTINE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'albaomarieltine0812@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4670, 'CXI11424', 'VILLADOR, EDDONNIE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'eddonnevillador22@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4671, 'CXI00944', 'PEDROSA, ROWENA ROSALES', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'pedrosarowena2020@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4672, 'CXI11671', 'PADRIQUELA, JOREIANNE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'yannpadriquela@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4673, 'CXI11389', 'ZAMORA, DEBORAH JANE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'deborahjanezamora022@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4674, 'CXI11692', 'ARGAME, PIERRE PAUL JETERSEN T.', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'jetersentorres@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4675, 'CXI11587', 'BERNASOR, GRACE ANN LABIAL', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'bernasorgraceyy@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4676, 'CXI11816', 'BERUNIO, ARBIE ROSLIN', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'fordabarbie@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4677, 'CXI11773', 'LEGASPI, JERALD HOBBIE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'maxivhie@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4678, 'CXI11930', 'TAGLE, MARY JOYCE', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'kaishiro.bajie07@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4679, 'CXI01096', 'NAPIERE, JOHN LOYD ESPENIDA', 'AB CALLBACK TEAM', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'napiere.johnloyd091804@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4680, 'CXI00915', 'ALMOGUERA, WELLA JOY DEL BARRIO', 'AB CALLBACK TEAM', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'lellajoy25@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4681, 'CXI11294', 'PABUA, KRISNIEL', 'AB CALLBACK TEAM', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'krisnielpaul@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4682, 'CXI00861', 'PARRA, MARINA SUAREZ', 'PRE ARRIVALS', 'CRUZ, KIM CARLO', 'Christopher Paller', 'marjieparra@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4683, 'CXI11420', 'GARCIA, MONICA', 'PRE ARRIVALS', 'CRUZ, KIM CARLO', 'Christopher Paller', 'garciamonicatulao@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4684, 'CXI11993', 'SANTIAGO, ENJERICA', 'PRE ARRIVALS', 'CRUZ, KIM CARLO', 'Christopher Paller', 'enjericasantiago06@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4685, 'CXI11986', 'DE JESUS, DESIREE MAXINE', 'PRE ARRIVALS', 'CRUZ, KIM CARLO', 'Christopher Paller', 'dmaxine.dejesus@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4686, 'CXI12002', 'DE JESUS, LANCE', 'PRE ARRIVALS', 'CRUZ, KIM CARLO', 'Christopher Paller', 'lanceruidejesus@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4687, 'CXI11775', 'ECHENIQUE, JOHN', 'PRE ARRIVALS', 'CRUZ, KIM CARLO', 'Christopher Paller', 'jbechenique@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4688, 'CXI12073', 'CINENSE, ANNA LIZA MAGLANA', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4689, 'CXI12071', 'DANAO, KARYLL JOY N/A', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4690, 'CXI12034', 'ACEVEDA, MA. ALLYSSA', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'mariaallyssaaceveda@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4691, 'CXI12030', 'OLATAN, KRISTINE NUEL', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'kristinenuel@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4692, 'CXI11995', 'DIAZ, SHAINA', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'shainadiaz92@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4693, 'CXI12038', 'BUENAFLOR, QUEENIE ROSE', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'buenaflorqueen3@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4694, 'CXI11868', 'ALVAREZ, ARA MAY', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'alvarezaramay2@fgmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4695, 'CXI11689', 'MAGBANUA, HARTWELL PATRICK P.', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'saberhartwellmagbanua@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4696, 'CXI12024', 'DIENTE, AJ MAE', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'dienteajmae@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4697, 'CXI11769', 'REGONDOLA CHAZ MARCO A', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'chazmarcoaregondola@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4698, 'CXI12029', 'ANCIADO, DIANNE PAUNIN', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4699, 'CXI00864', 'LAXINA, ROSE MASANGKAY', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'rosemasangkay26@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4700, 'CXI11537', 'CAYABYAB. JUBAIL DELA CRUZ', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'jubaildelacruzcayabyab@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4701, 'CXI11296', 'PAGSISIHAN, ANGEL', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'angeljoanne340@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4702, 'CXI11772', 'TANGLAO, KERVIN', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'vinrek03@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4703, 'CXI11182', 'SIBUMA, JO-AN', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'joansibuma1018@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4704, 'CXI11600', 'BENAVIDES, RESTER JR II. AWITIN', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'benavidesjr.rester@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4705, 'CXI11325', 'GUEVARA, JASON MARK', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'jasonguevara1405@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4706, 'CXI01059', 'MARCELINO, MICHELLE', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'marcelinomichelle1@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4707, 'CXI11987', 'CAPILI, MARIA KATRINA', 'AUTOBOOKING', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4708, 'CXI11667', 'LACORTE, CAITLIN JAY', 'AB CALLBACK TEAM', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'caitlinlacorte@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4709, 'CXI11666', 'RAVELO, MEGUMI COLLENE', 'AB CALLBACK TEAM', 'AREVALO PO, MARK FRANCES', 'Christopher Paller', 'megumiravelo05@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4710, 'CXI12086', 'ORAYA, IRENE', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4711, 'CXI12072', 'AQUINO, JAMIL T.', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4712, 'CXI11696', 'TABLAN, ROMELYN, REGIS', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', 'rhomztablan596@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4713, 'CXI11541', 'CONSTANTINO, FRANCES KATHERINE', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', 'iamfrance17@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4714, 'CXI12040', 'BELTRAN, BERNADETH', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4715, 'CXI12025', 'MATARONG, CLARISSA JOY', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4716, 'CXI11994', 'UGALI, JOAQUIN', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4717, 'CXI11866', 'UDTOHAN, YVAN', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', 'navyudtohan25@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4718, 'CXI11867', 'MALUBAY, RODNEY SIOCON', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', 'rodsammal020518@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4719, 'CXI11320', 'MANUEL, CRISLYN JOY', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', 'crislynjoymanuel122603@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4720, 'CXI11181', 'BALVERDE, ROBIN', 'AUTOBOOKING - ON', 'CORRAL, GREGON', 'Christopher Paller', 'robincbalverde.26@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4721, 'CXI00468', 'ALAGAO, ANTHONY CASAIS', 'AUTOBOOKING - ON', 'CORRAL, GREGON', 'Christopher Paller', 'aalagao91@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4722, 'CXI11817', 'GAVIOLA, ROMNUEL', 'AUTOBOOKING - ON', 'CORRAL, GREGON', 'Christopher Paller', 'romgaviola14@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4723, 'CXI11472', 'TIONGCO, BRYAN EDRIC', 'AB CALLBACK TEAM', 'CORRAL, GREGON', 'Christopher Paller', 'tiongcobryan27@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4724, 'CXI11184', 'ESPARAGOZA, JERICHO', 'AB CALLBACK TEAM', 'CORRAL, GREGON', 'Christopher Paller', 'JERICHOESPARAGOZA7@GMAIL.COM', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4725, 'CXI01045', 'BERSAMIN, LEONARD MACAPAGAL', 'AB CALLBACK TEAM', 'CORRAL, GREGON', 'Christopher Paller', 'leobersamin@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4726, 'CXI00916', 'HERRERA, IRENE CRUZ', 'AUTOBOOKING - ON', 'CORRAL, GREGON', 'Christopher Paller', 'ireneirene0924@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4727, 'CXI00199', 'MAMATE, ROALD DEGALA', 'AUTOBOOKING', 'CXI MNGT', 'Christopher Paller', 'x15rowaldodmx@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4728, 'CXI11869', 'ABOLA, MARGARITA ANN', 'AUTOBOOKING', 'CORRAL, GREGON', 'Christopher Paller', 'margarettee.yyy@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4729, 'CXI11776', 'GUARIÑO, RAIN', 'AUTOBOOKING', 'CRUZ, KIM CARLO', 'Christopher Paller', 'kainos102820@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4730, 'CXI11619', 'RIBLE, CASPER KENT D.', 'AUTOBOOKING', 'ABILES, CHARLZ DANEVER', 'Christopher Paller', 'casperkentrible@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4731, 'CXI12074', 'REYES, WARREN LEIGH', 'AUTOBOOKING', 'SEVILLA, ANN CLAUDETTE', 'Christopher Paller', NULL, '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4732, 'CXI01054', 'SAYAS, FE', 'CLC MANAGED', 'CXI MNGT', 'Christopher Paller', 'pausayas21@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4733, 'CXI11186', 'YABUT, ANGELIKA GADIL', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'angelikayabut5@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4734, 'CXI01003', 'CASILAGAN, CHRISTIAN GARCIA', 'QA SPECIALIST', 'CERTEZA, NOVELYN', 'Abbes Saldivia', 'garciacasey591@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4735, 'CXI12027', 'SARIA, KAYE EMERYLL', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'CXI11776', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4736, 'CXI12039', 'BALBIN, JANREB', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'CXI11776', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4737, 'CXI12033', 'FAJARDO, JOHN RONALD', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'CXI11776', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4738, 'CXI12035', 'TRIÑANES, SALVADOR CONNER', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'CXI11776', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4739, 'CXI00164', 'REYES, ESMERALDO ROCO', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'esmeraldoreyes30@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4740, 'CXI11360', 'MATRE, KAREN', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'ken_matre@yahoo.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4741, 'CXI11949', 'RAMOS, JOHN PAUL', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'jpaulramos3paulx@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4742, 'CXI11944', 'SALCEDO, CHRIZ ANNE', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'salcedochrizanne@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4743, 'CXI11953', 'AURELIO, SAMANTHA KEITH', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'samantha.keith08@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4744, 'CXI11947', 'VILLANUEVA, SIR NORJOHN GERARD', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'norjohnvillanueva@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4745, 'CXI11954', 'BRAGA, EJAY', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'jaycreaten000@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4746, 'CXI11948', 'OCAMPO, KYLA', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'kylaaocampo17@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4747, 'CXI11951', 'POJAS, ROJE MAE', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'pojasrojemae@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4748, 'CXI11952', 'GOROSPE, JASON LEMUEL', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'zlienyuri@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1),
(4749, 'CXI11950', 'SICATIN, KRISHA', 'CLC MANAGED', 'SAYAS, FE', 'Christopher Paller', 'khrishaannes@gmail.com', '2025-07-24 01:14:15', '2025-07-24 01:14:15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `management`
--

CREATE TABLE `management` (
  `id` int(11) NOT NULL,
  `cxi_id` varchar(10) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `management`
--

INSERT INTO `management` (`id`, `cxi_id`, `fullname`, `department`, `email`, `role`, `created_at`, `is_active`, `last_activity`) VALUES
(1, 'CXI11792', 'SALDIVIA, ABRAHAM NEBRES', 'TQAM', 'sample@gmail.com', 'TQM', '2025-07-28 12:18:51', 1, NULL),
(2, 'CXI11448', 'CERTEZA, NOVELYN LANUZO', 'QA SUP', NULL, 'QA SUP', '2025-07-28 12:18:51', 1, NULL),
(3, 'CXI12091', 'PAMULAR, ERNISON B', 'QA SUP', NULL, 'QA SUP', '2025-07-28 12:18:51', 1, NULL),
(4, 'CXI11383', 'NUEZ, RAINE JASMYN', 'FB / UAT', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(5, 'CXI11204', 'PAGUIA, AL CHRISTIAN AMIT', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(6, 'CXI00823', 'CADSAWAN, JOHANNA MORALES', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(7, 'CXI01065', 'MAGNO, CHERISSE', 'MAI - ECAMPUS - DI', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(8, 'CXI00006', 'MIRALLES, JEBONNIE ADONIS', 'SOURCING - AIRLINES', NULL, 'TLT', '2025-07-28 12:18:51', 1, NULL),
(9, 'CXI00279', 'MOPIA, PATRICK BERNARDO', 'GROUPS', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(10, 'CXI00357', 'JAEN, ALBERT DOMENS', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(11, 'CXI00370', 'NAPIERE, MINETTE ESPENIDA', 'SINGLES', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(12, 'CXI00308', 'PICAZO, CHRISTIAN KING BALANQUIT', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(13, 'CXI00467', 'BAGACAY, ROGELIO BIATO', 'SINGLES', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(14, 'CXI00450', 'VIDOR, EUGENE', 'AA BILLING', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(15, 'CXI11330', 'MORADAS, JAYZIN CASTRO', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(16, 'CXI11193', 'FRANCISCO, JONALYN', 'AA BILLING', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(17, 'CXI11653', 'YENKO, ANNA LIZA BAYSA', 'CREWREZ / REPORTS', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(18, 'CXI00160', 'LAZARO, ALLIYAH CAMILLE DIZON', 'CREWREZ / AIR', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(19, 'CXI00816', 'CHAVES, JEHLIAN ALIJAH BAYOT', 'LEGACY / RECON', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(20, 'CXI11523', 'TIMING, YBRAHIM ORDIALES', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(21, 'CXI11675', 'ROSETE, KELVIN ABALON', 'BLOCKERS', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(22, 'CXI00931', 'REYLES, NICOLE ANN KLEIN VALENTOS', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(23, 'CXI00712', 'BAILLO, CRISTINE QUIRANTE', 'BLOCKERS', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(24, 'CXI00922', 'NACIONAL, REIGNALYN RUBIO', 'BLOCKERS', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(25, 'CXI00361', 'CONCEPCION, CHERRY MAY CATEDRILLA', 'AMERICAN AIRLINES', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(26, 'CXI11590', 'LATORENO, ABEJUN LASALA', 'AMERICAN AIRLINES', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(27, 'CXI01038', 'VIDA, RAVIEL ERNEST LOPEZ', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(28, 'CXI00347', 'GALLENO, GENREV ZION BOLO', 'APAC', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(29, 'CXI11233', 'PONTAWE, ALVIN', 'US CREW', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(30, 'CXI11219', 'PIDOT, LAWRENCE TABALNO', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(31, 'CXI00045', 'PARIL, MARWIN ARGA', 'CLC EMERGENCY', NULL, 'TLT', '2025-07-28 12:18:51', 1, NULL),
(32, 'CXI11142', 'CALMA, RUSSEL JAY CRUZ', 'CLC EMERGENCY', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(33, 'CXI00020', 'ARANZANSO, BESSIE MENDOZA', 'CLC EMERGENCY', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(34, 'CXI11188', 'CARALDE, KENNY ROBERT DELA CRUZ', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(35, 'CXI00014', 'AGUIBITIN, ERVIN JOHN REYES', 'HOTELS', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(36, 'CXI11303', 'MARILLA, MARY ANN ADVINCULA', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(37, 'CXI01030', 'SANCHEZ, ALTHEA MABEL BUENCONSEJO', 'ALE ADMIN', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(38, 'CXI01039', 'VALENCIA, MARTIN RAY MIRANDA', 'ALE ADMIN', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(39, 'CXI00818', 'SAPNO, MARIE LINA', 'FOLIO CHASERS', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(40, 'CXI00699', 'BAILLO, MARY HAIL QUIRANTE', 'FOLIO CHASERS', NULL, 'TLT', '2025-07-28 12:18:51', 1, NULL),
(41, 'CXI11385', 'NEPOMUCENO, ARVIN AVILA', 'FOLIO CHASERS', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(42, 'CXI00529', 'ICALIA, EZEKIEL', 'BNSF', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(43, 'CXI11421', 'SEVILLA, ANN CLAUDETTE', 'AUTOBOOKING', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(44, 'CXI01095', 'ABILES, CHARLZ DANEVER GUIBIJAR', 'AUTOBOOKING', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(45, 'CXI11180', 'PACSON, ARYANNA', 'BNSF', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(46, 'CXI12060', 'CORRAL, GREGON', 'AUTOBOOKING', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(47, 'CXI12057', 'CRUZ, KIM CARLO', 'AUTOBOOKING', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(48, 'CXI11483', 'AREVALO PO, MARK FRANCES', 'AUTOBOOKING', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(49, 'CXI00862', 'SOROSORO, EDNETH JOHN HERRADURA', 'BNSF', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(50, 'CXI01053', 'FULLO, VERONICA SAMONTEZA', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(51, 'CXI00899', 'RODRIGUEZ ALPHA BLESS', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(52, 'CXI00199', 'MAMATE, ROALD DEGALA', 'AUTOBOOKING', NULL, 'TL / SUP', '2025-07-28 12:18:51', 1, NULL),
(53, 'CXI01054', 'SAYAS, FE', 'CLC MANAGED', NULL, 'SME / TL', '2025-07-28 12:18:51', 1, NULL),
(54, 'CXI11186', 'YABUT, ANGELIKA GADIL', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL),
(55, 'CXI01003', 'CASILAGAN, CHRISTIAN GARCIA', 'QA SPECIALIST', NULL, 'QA', '2025-07-28 12:18:51', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `operations_managers`
--

CREATE TABLE `operations_managers` (
  `id` int(11) NOT NULL,
  `cxi_id` varchar(20) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operations_managers`
--

INSERT INTO `operations_managers` (`id`, `cxi_id`, `fullname`, `department`, `email`, `is_active`, `created_at`) VALUES
(7, 'CXI00004', 'MENDOZA, BENEDICT PATASIN', 'operations manager', 'bmendoza@communixinc.com', 1, '2025-07-24 11:16:52'),
(8, 'CXI00008', 'RIVERA, CHARISE CALDERON', 'operations manager', 'crivera@communixinc.com', 1, '2025-07-24 11:17:29'),
(9, 'CXI11768', 'PALLER, CHRISTOPHER SAYAS', 'operations manager', 'cl.paller@communixinc.com', 1, '2025-07-24 11:19:06'),
(10, 'CXI00141', 'BIER, FREDRIECH VAUGHN LACONSAY', 'operations manager', 'fred.bier@communixinc.com', 1, '2025-07-24 11:19:32'),
(11, 'CXI00732', 'PHAY BARRAMEDA', 'operations manager', 'phay.barrameda@communixinc.com', 1, '2025-07-24 11:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `tardiness`
--

CREATE TABLE `tardiness` (
  `id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `operation_manager` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_of_incident` date NOT NULL,
  `types` enum('Late','Undertime') NOT NULL,
  `minutes_late` int(11) NOT NULL,
  `shift` varchar(50) NOT NULL,
  `ir_form` varchar(100) DEFAULT NULL,
  `accumulation_count` int(11) DEFAULT 1,
  `timestamp` varchar(20) DEFAULT NULL,
  `sub_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` varchar(20) DEFAULT NULL,
  `expires_at` datetime GENERATED ALWAYS AS (`created_at` + interval 1 month) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tardiness`
--

INSERT INTO `tardiness` (`id`, `month`, `employee_id`, `full_name`, `department`, `supervisor`, `operation_manager`, `email`, `date_of_incident`, `types`, `minutes_late`, `shift`, `ir_form`, `accumulation_count`, `timestamp`, `sub_name`, `created_at`, `email_sent`, `email_sent_at`) VALUES
(14, 'Jul 2025', 'CXI11457', 'BAUTISTA, SHARMAINE NICOLE', 'CREWREZ', 'YENKO, ANNA LIZA', 'Fred Bier', 'sharmainenicole.bautista@gmail.com', '2025-07-28', 'Late', 12, '7:00 PM - 4:00 AM', 'for accumulation', 1, '11:43 PM', 'SLT NICO', '2025-07-28 15:43:00', 0, NULL),
(15, 'Jul 2025', 'CXI00525', 'TORRES, JUAN CARLO PABLO', 'SLT', 'CXI MNGT', 'Phay Barrameda', 'juancarlotorres06@gmail.com', '2025-07-28', 'Late', 5, '7:00 PM - 4:00 AM', 'for accumulation', 1, '11:47 PM', 'SLT NICO', '2025-07-28 15:47:28', 0, NULL),
(16, 'Jul 2025', 'CXI11899', 'GALINATO, NICO', 'SLT', 'TORRES, JUAN CARLO', 'Phay Barrameda', 'nicologalinato80@gmail.com', '2025-07-29', 'Late', 15, '3:30 AM - 1:00 PM', 'For accumulation', 1, '6:32 AM', 'SLT NICO', '2025-07-28 22:32:05', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `sub_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `slt_email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_attempts` int(11) DEFAULT 0,
  `last_failed_login` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT NULL,
  `last_modified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `sub_name`, `password`, `slt_email`, `created_at`, `login_attempts`, `last_failed_login`, `locked_until`, `role`, `is_active`, `last_activity`, `last_modified`) VALUES
(1, 'cxi11899', 'GALINATO, NICO', 'SLT NICO', '$2y$10$Nq1m.u1mc22CgjCYuRzHp.16IMLEc5PvKtFeZpPt0ej7V9xhIghc6', 'nicolo.galinato@communixinc.com', '2025-07-17 15:24:12', 0, NULL, NULL, 'admin', 1, '2025-07-28 22:33:15', '2025-07-28 22:33:15'),
(37, 'cxi00525', 'JC TORRES', 'SLT JC', '$2y$10$fSnVChxO1EvhBhXFPkl/HOGC.uxik2kHay6cvlOKwkaIgXju.ASqS', 'juan.torres@communixinc.com', '2025-07-17 19:44:34', 0, NULL, NULL, 'admin', 1, '2025-07-28 20:38:52', '2025-07-28 20:38:52'),
(38, 'cxi00730', 'RG DUTERTE', 'SLT RG', '$2y$10$4Tnf9CRtLqS4fvFM2Otjme8zCSX6bbtUDiSgNVQ8IGUgo/VBZ4gqe', 'rg.duterte@communixinc.com', '2025-07-17 19:45:03', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-24 15:08:02'),
(39, 'cxi11647', 'ALEXANDER RAY OLAES', 'SLT ALEX', '$2y$10$drhplWGrgo0Gz7nbnRXV.OcYwpieFAkaKROp/xrH2Hj5hNxnyEItW', 'a.olaes@communixinc.com', '2025-07-17 19:45:22', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-24 15:08:02'),
(40, 'cxi11652', 'ANGKIKO, MIGUEL JEAN', 'SLT MIGS', '$2y$10$QfoRP/1fsDr2jyaTZTSQfORiDix1fHi..f4KK9p.lzU38sk7oWv7S', 'miguel.angkiko@communixinc.com', '2025-07-17 19:45:49', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-24 15:08:02'),
(41, 'cxi11664', 'IVERSON LOMAT', 'SLT IVER', '$2y$10$ju/wgHSL1tQJeM4/weg5nuX3oGwKhBSOg3fcGjRZUIlzELPwtPeXi', 'iverson.lomat@communixinc.com', '2025-07-17 19:46:06', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-25 18:06:10'),
(42, 'cxi11812', 'CHRISTIAN MONTOYA', 'SLT CIAN', '$2y$10$jyCYyY.UBtHPSBlKONCrpeZ15N4u0VHQw20/LxV0q6fxvqpuAa2cy', 'c.montoya@communixinc.com', '2025-07-17 19:46:35', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-24 15:08:02'),
(56, 'CXI12100', 'MONEDA ROLANDO', 'SLT OLAN', '$2y$10$JpoID58r9ghFzquMZLVXHOzd9v.NuKZLu3el1aarMZmZ9U2leJpQS', 'R.moneda@communixinc.com', '2025-07-24 17:08:38', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-24 17:08:38'),
(57, 'CXI00732', 'APRIL BARRAMEDA', 'SOM PHAY', '$2y$10$inCw3t1pr0JEyWUYHOxpYONz28ucTCEPyZeacQvVFJFfPIGlbXE3i', 'phay.barrameda@communixinc.com', '2025-07-25 01:55:13', 0, NULL, NULL, 'admin', 1, NULL, '2025-07-27 17:06:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absenteeism`
--
ALTER TABLE `absenteeism`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `management`
--
ALTER TABLE `management`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operations_managers`
--
ALTER TABLE `operations_managers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cxi_id` (`cxi_id`);

--
-- Indexes for table `tardiness`
--
ALTER TABLE `tardiness`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fullname` (`fullname`) USING BTREE,
  ADD UNIQUE KEY `username` (`username`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absenteeism`
--
ALTER TABLE `absenteeism`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4752;

--
-- AUTO_INCREMENT for table `management`
--
ALTER TABLE `management`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `operations_managers`
--
ALTER TABLE `operations_managers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tardiness`
--
ALTER TABLE `tardiness`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
