-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 17 dic, 2013 at 09:03 
-- Versione MySQL: 5.1.37
-- Versione PHP: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `catastogrotte`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `ACARCOD`
--

DROP TABLE IF EXISTS `ACARCOD`;
CREATE TABLE IF NOT EXISTS `ACARCOD` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ACARCOD` varchar(255) DEFAULT NULL,
  `ACAR` varchar(255) DEFAULT NULL,
  `PROV` varchar(255) NOT NULL,
  `DESCRIZIONE` text NOT NULL,
  `GRUPPI` text NOT NULL,
  `NOTE` text NOT NULL,
  `image1` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  `surface` varchar(255) NOT NULL,
  `cities` varchar(255) NOT NULL,
  `mountaincommunity` varchar(255) NOT NULL,
  `watershed` varchar(255) NOT NULL,
  `activequarries` varchar(255) NOT NULL,
  `inactivequarries` varchar(255) NOT NULL,
  `activelandfills` varchar(255) NOT NULL,
  `inactivelandfills` varchar(255) NOT NULL,
  `lithological` text NOT NULL,
  `geomorphology` text NOT NULL,
  `hydrogeological` text NOT NULL,
  `caving` text NOT NULL,
  `landcover` text NOT NULL,
  `othercharacteristics` text NOT NULL,
  `aquifers` text NOT NULL,
  `photo1` varchar(255) NOT NULL,
  `authorphoto1` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dump dei dati per la tabella `ACARCOD`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `AGE`
--

DROP TABLE IF EXISTS `AGE`;
CREATE TABLE IF NOT EXISTS `AGE` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `NPRO` double DEFAULT NULL,
  `AGE` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=102 ;

--
-- Dump dei dati per la tabella `AGE`
--

INSERT INTO `AGE` (`ID`, `NPRO`, `AGE`) VALUES
(1, 1, 'Olocene'),
(2, 2, 'Pleistocene Superiore'),
(3, 3, 'Pleistocene Medio'),
(4, 4, 'Pleistocene Inferiore'),
(5, 5, 'Pleistocene (Tirreniano)'),
(6, 6, 'Pleistocene (Milazziano)'),
(7, 7, 'Pleistocene (Siciliano)'),
(8, 8, 'Pleistocene (Emiliano)'),
(9, 9, 'Pleistocene (Calabriano)'),
(10, 10, 'Pleistocene'),
(11, 11, 'Pliocene (Piacenziano)'),
(12, 12, 'Pliocene (Tabianiano)'),
(13, 13, 'Pliocene'),
(14, 14, 'Miocene (Messiniano)'),
(15, 15, 'Miocene (Tortoniano)'),
(16, 16, 'Miocene (Serravalliano)'),
(17, 17, 'Miocene (Langhiano)'),
(18, 18, 'Miocene (Burdigaliano)'),
(19, 19, 'Miocene (Aquitaniano)'),
(20, 20, 'Miocene'),
(21, 21, 'Neogene'),
(22, 22, 'Oligocene (Cattiano)'),
(23, 23, 'Oligocene (Rupeliano)'),
(24, 24, 'Oligocene (Lattorfiano)'),
(25, 25, 'Oligocene (Stampiano)'),
(26, 26, 'Oligocene (Sannoisiano)'),
(27, 27, 'Oligocene'),
(28, 28, 'Eocene (Priaboniano)'),
(29, 29, 'Eocene (Luteziano)'),
(30, 30, 'Eocene (Cuisiano)'),
(31, 31, 'Eocene'),
(32, 32, 'Paleocene (Ilerdiano)'),
(33, 33, 'Paleocene (Thanetiano)'),
(34, 34, 'Paleocene (Montiano)'),
(35, 35, 'Paleocene (Daniano)'),
(36, 36, 'Paleocene'),
(37, 37, 'Paleogene'),
(38, 38, 'Cretaceo Sup. (Maastrichtiano)'),
(39, 39, 'Cretaceo Sup. (Campaniano)'),
(40, 40, 'Cretaceo Sup. (Santoniano)'),
(41, 41, 'Cretaceo Sup. (Coniaciano)'),
(42, 42, 'Cretaceo Sup. (Senoniano)'),
(43, 43, 'Cretaceo Sup. (Turoniano)'),
(44, 44, 'Cretaceo Sup. (Cenomaniano)'),
(45, 45, 'Cretaceo Superiore'),
(46, 46, 'Cretaceo Inf. (Albiano)'),
(47, 47, 'Cretaceo Inf. (Aptiano)'),
(48, 48, 'Cretaceo Inf. (Barremiano)'),
(49, 49, 'Cretaceo Inf. (Hauteriviano)'),
(50, 50, 'Cretaceo Inf. (Valanginiano)'),
(51, 51, 'Cretaceo Inf. (Berriasiano)'),
(52, 52, 'Cretaceo Inf. (Neocomiano)'),
(53, 53, 'Cretaceo Inferiore'),
(54, 54, 'Cretaceo'),
(55, 55, 'Giurassico Sup. (Titoniano)'),
(56, 56, 'Giurassico Sup. (Kimmeridgiano)'),
(57, 57, 'Giurassico Sup. (Oxfordiano)'),
(58, 58, 'Giurassico Superiore'),
(59, 59, 'Giurassico Med. (Calloviano)'),
(60, 60, 'Giurassico Med. (Batoniano)'),
(61, 61, 'Giurassico Med. (Bajociano)'),
(62, 62, 'Giurassico Med. (Aaleniano)'),
(63, 63, 'Giurassico Medio'),
(64, 64, 'Giurassico Inf. (Toarciano)'),
(65, 65, 'Giurassico Inf. (Pliensbachiano)'),
(66, 66, 'Giurassico Inf. (Sinemuriano)'),
(67, 67, 'Giurassico Inf. (Hettangiano)'),
(68, 68, 'Giurassico Inf. (Domeriano)'),
(69, 69, 'Giurassico Inf. (Carixiano)'),
(70, 70, 'Giurassico Inferiore'),
(71, 71, 'Giurassico'),
(72, 72, 'Triassico Sup. (Retico)'),
(73, 73, 'Triassico Sup. (Norico)'),
(74, 74, 'Triassico Sup. (Carnico)'),
(75, 75, 'Triassico Superiore'),
(76, 76, 'Triassico Med. (Ladinico)'),
(77, 77, 'Triassico Med. (Anisico)'),
(78, 78, 'Triassico Medio'),
(79, 79, 'Triassico Inf. (Scitico)'),
(80, 80, 'Triassico'),
(81, 81, 'Permiano Superiore'),
(82, 82, 'Permiano Inferiore'),
(83, 83, 'Permiano'),
(84, 84, 'Carbonifero Superiore'),
(85, 85, 'Carbonifero Inferiore'),
(86, 86, 'Carbonifero'),
(87, 87, 'Devoniano Superiore'),
(88, 88, 'Devoniano Medio'),
(89, 89, 'Devoniano Inferiore'),
(90, 90, 'Devoniano'),
(91, 91, 'Siluriano Superiore'),
(92, 92, 'Siluriano Inferiore'),
(93, 93, 'Siluriano'),
(94, 94, 'Ordoviciano Superiore'),
(95, 95, 'Ordoviciano Inferiore'),
(96, 96, 'Ordoviciano'),
(97, 97, 'Cambriano'),
(98, 98, 'Paleozoico'),
(99, 99, 'Archeozoico'),
(100, 100, 'Eocene Inferiore'),
(101, NULL, 'Cenozoico Superiore');

-- --------------------------------------------------------

--
-- Struttura della tabella `ATTACHMENTS`
--

DROP TABLE IF EXISTS `ATTACHMENTS`;
CREATE TABLE IF NOT EXISTS `ATTACHMENTS` (
  `ID` varchar(255) NOT NULL,
  `NUMCAVE` varchar(255) NOT NULL,
  `NAME` varchar(255) NOT NULL,
  `FILE` varchar(255) NOT NULL,
  `DESC` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `ATTACHMENTS`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `Bibliografia`
--

DROP TABLE IF EXISTS `Bibliografia`;
CREATE TABLE IF NOT EXISTS `Bibliografia` (
  `ID` double NOT NULL DEFAULT '0',
  `Autori` varchar(255) DEFAULT NULL,
  `Titolo` varchar(255) DEFAULT NULL,
  `Rivista` varchar(255) DEFAULT NULL,
  `Editore` varchar(255) DEFAULT NULL,
  `Citta` varchar(255) DEFAULT NULL,
  `Prov` varchar(255) DEFAULT NULL,
  `Anno` varchar(255) DEFAULT NULL,
  `N` varchar(255) DEFAULT NULL,
  `pp` varchar(255) DEFAULT NULL,
  `Zona` varchar(255) DEFAULT NULL,
  `Grotte` text,
  `Riassunto` text,
  `Rilievi` longtext,
  `Foto` varchar(255) DEFAULT NULL,
  `Disegni` varchar(255) DEFAULT NULL,
  `Argomento` varchar(255) DEFAULT NULL,
  `Grotte_modificate` text,
  `Rilievi_modificati` text,
  `Grotte modificate` text NOT NULL,
  `Rilievi modificati` text NOT NULL,
  `recordinsert` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `file1` varchar(255) NOT NULL,
  `file2` varchar(255) NOT NULL,
  `file3` varchar(255) NOT NULL,
  `Fauna` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `Bibliografia`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `CART`
--

DROP TABLE IF EXISTS `CART`;
CREATE TABLE IF NOT EXISTS `CART` (
  `ID` bigint(20) NOT NULL,
  `NUMCAVE` varchar(255) NOT NULL,
  `NAME` varchar(255) NOT NULL,
  `CART` varchar(255) NOT NULL,
  `DATE` varchar(255) NOT NULL,
  `recordinsert` varchar(255) NOT NULL,
  `AUTHOR` varchar(255) NOT NULL,
  `LICENSE` varchar(255) NOT NULL,
  `DESC` text NOT NULL,
  `FILE` varchar(255) NOT NULL,
  `FILEDWG` varchar(255) NOT NULL,
  `FILEKML` varchar(255) NOT NULL,
  `PRECISIONE` varchar(255) NOT NULL,
  `MAKERS` text NOT NULL,
  `PDF` varchar(255) NOT NULL,
  `groupview` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `NUMCAVE` (`NUMCAVE`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `CART`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `COMUNE`
--

DROP TABLE IF EXISTS `COMUNE`;
CREATE TABLE IF NOT EXISTS `COMUNE` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `COMUNE` varchar(50) DEFAULT NULL,
  `CODE` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ID` (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=241 ;

--
-- Dump dei dati per la tabella `COMUNE`
--

INSERT INTO `COMUNE` (`ID`, `COMUNE`, `CODE`) VALUES
(1, 'AIROLE', '8001'),
(2, 'ALASSIO', '9001'),
(3, 'ALBENGA', '9002'),
(4, 'ALBISOLA SUPERIORE', '9004'),
(5, 'ALBISOLA MARINA', '9003'),
(6, 'ALTARE', '9005'),
(7, 'AMEGLIA', '11001'),
(8, 'ANDORA', '9006'),
(9, 'APRICALE', '8002'),
(11, 'AQUILA DI ARROSCIA', '8003'),
(12, 'ARCOLA', '11002'),
(13, 'ARENZANO', '10001'),
(14, 'ARMO', '8004'),
(15, 'ARNASCO', '9007'),
(16, 'AURIGO', '8005'),
(17, 'AVEGNO', '10002'),
(18, 'BADALUCCO', '8006'),
(19, 'BAIARDO', '8007'),
(20, 'BALESTRINO', '9008'),
(21, 'BARDINETO', '9009'),
(22, 'BARGAGLI', '10003'),
(23, 'BERGEGGI', '9010'),
(24, 'BEVERINO', '11003'),
(25, 'BOGLIASCO', '10004'),
(26, 'BOISSANO', '9011'),
(27, 'BOLANO', '11004'),
(28, 'BONASSOLA', '11005'),
(29, 'BORDIGHERA', '8008'),
(30, 'BORGHETTO D''ARROSCIA', '8009'),
(32, 'BORGHETTO DI VARA', '11006'),
(33, 'BORGHETTO SANTO SPIRITO', '9012'),
(34, 'BORGIO VEREZZI', '9013'),
(35, 'BORGOMARO', '8010'),
(36, 'BORMIDA', '9014'),
(37, 'BORZONASCA', ''),
(38, 'BRUGNATO', ''),
(39, 'BUSALLA', '10006'),
(40, 'CAIRO MONTENOTTE', '9015'),
(41, 'CALICE AL CORNOVIGLIO', ''),
(42, 'CALICE LIGURE', '9016'),
(43, 'CALIZZANO', '9017'),
(44, 'CAMOGLI', '10007'),
(45, 'CAMPO LIGURE', ''),
(46, 'CAMPOMORONE', '10009'),
(47, 'CAMPOROSSO', '8011'),
(48, 'CARASCO', ''),
(49, 'CARAVONICA', '8012'),
(50, 'CARCARE', ''),
(51, 'CARPASIO', ''),
(52, 'CARRO', ''),
(53, 'CARRODANO', ''),
(54, 'CASANOVA LERRONE', ''),
(55, 'CASARZA LIGURE', ''),
(56, 'CASELLA', ''),
(57, 'CASTEL VITTORIO', ''),
(58, 'CASTELBIANCO', ''),
(59, 'CASTELLARO', ''),
(60, 'CASTELNUOVO MAGRA', ''),
(61, 'CASTELVECCHIO DI ROCCA BARBENA', '9021'),
(62, 'CASTIGLIONE CHIAVARESE', '10013'),
(63, 'CELLE LIGURE', '9022'),
(64, 'CENGIO', ''),
(65, 'CERANESI', '10014'),
(66, 'CERIALE', '9024'),
(67, 'CERIANA', ''),
(68, 'CERVO', '8017'),
(69, 'CESIO', ''),
(70, 'CHIAVARI', '10015'),
(71, 'CHIUSANICO', '8019'),
(72, 'CICAGNA', ''),
(73, 'CIPRESSA', ''),
(74, 'CISANO SUL NEVA', '9025'),
(75, 'CIVEZZA', ''),
(76, 'COGOLETO', ''),
(77, 'COGORNO', ''),
(78, 'COREGLIA LIGURE', ''),
(79, 'COSIO DI ARROSCIA', '8023'),
(80, 'COSSERIA', ''),
(81, 'COSTARAINERA', ''),
(82, 'CROCEFIESCHI', ''),
(83, 'DAVAGNA', '10021'),
(84, 'DEGO', ''),
(85, 'DEIVA MARINA', '11012'),
(86, 'DIANO ARENTINO', '8025'),
(87, 'DIANO CASTELLO', ''),
(88, 'DIANO MARINA', ''),
(89, 'DIANO SAN PIETRO', ''),
(90, 'DOLCEACQUA', '8029'),
(91, 'DOLCEDO', '8030'),
(92, 'ERLI', '9028'),
(95, 'FASCIA', ''),
(96, 'FAVALE DI MALVARO', ''),
(97, 'FINALE LIGURE', '9029'),
(98, 'FOLLO', ''),
(99, 'FONTANIGORDA', ''),
(100, 'FRAMURA', '11014'),
(101, 'GARLENDA', ''),
(102, 'GENOVA', '10025'),
(103, 'GIUSTENICE', '9031'),
(104, 'GIUSVALLA', ''),
(105, 'GORRETO', ''),
(106, 'IMPERIA', '8031'),
(107, 'ISOLA DEL CANTONE', '10027'),
(108, 'ISOLABONA', '8032'),
(109, 'LA SPEZIA', '11015'),
(110, 'LAIGUEGLIA', ''),
(111, 'LAVAGNA', ''),
(112, 'LEIVI', ''),
(113, 'LERICI', '11016'),
(114, 'LEVANTO', ''),
(115, 'LOANO', '9034'),
(116, 'LORSICA', ''),
(117, 'LUCINASCO', '8033'),
(118, 'LUMARZO', ''),
(119, 'MAGLIOLO', '9035'),
(120, 'MAISSANA', '11018'),
(121, 'MALLARE', '9036'),
(122, 'MASONE', ''),
(124, 'MASSIMINO', '9037'),
(125, 'MELE', ''),
(126, 'MENDATICA', '8034'),
(127, 'MEZZANEGO', ''),
(128, 'MIGNANEGO', '10035'),
(129, 'MILLESIMO', '9038'),
(130, 'MIOGLIA', '9039'),
(131, 'MOCONESI', ''),
(132, 'MOLINI DI TRIORA', '8035'),
(133, 'MONEGLIA', ''),
(134, 'MONTALTO LIGURE', '8036'),
(135, 'MONTEBRUNO', ''),
(136, 'MONTEGROSSO PIAN LATTE', '8037'),
(137, 'MONTEROSSO AL MARE', ''),
(138, 'MONTOGGIO', ''),
(139, 'MURIALDO', '9040'),
(140, 'NASINO', '9041'),
(141, 'NE', '10040'),
(142, 'NEIRONE', ''),
(143, 'NOLI', '9042'),
(144, 'OLIVETTA SAN MICHELE', '8038'),
(146, 'ONZO', ''),
(147, 'ORCO FEGLINO', '9044'),
(148, 'ORERO', ''),
(149, 'ORTONOVO', ''),
(150, 'ORTOVERO', ''),
(151, 'OSIGLIA', '9046'),
(152, 'OSPEDALETTI', ''),
(153, 'PALLARE', '9047'),
(154, 'PERINALDO', '8040'),
(155, 'PIANA CRIXIA', ''),
(156, 'PIETRA LIGURE', '9049'),
(157, 'PIETRABRUNA', '8041'),
(158, 'PIEVE DI TECO', '8042'),
(159, 'PIEVE LIGURE', ''),
(160, 'PIGNA', '8043'),
(161, 'PIGNONE', '11021'),
(162, 'PLODIO', '9050'),
(163, 'POMPEIANA', ''),
(164, 'PONTEDASSIO', '8045'),
(165, 'PONTINVREA', '9051'),
(166, 'PORNASSIO', '8046'),
(167, 'PORTOFINO', '10006'),
(168, 'PORTOVENERE', '11022'),
(169, 'PRELA''', '8047'),
(170, 'PROPATA', ''),
(171, 'QUILIANO', '9052'),
(172, 'RANZO', ''),
(173, 'RAPALLO', '10046'),
(174, 'RECCO', '10047'),
(175, 'REZZO', '8049'),
(176, 'REZZOAGLIO', ''),
(177, 'RIALTO', ''),
(178, 'RICCO'' DEL GOLFO DI SPEZIA', '11023'),
(179, 'RIOMAGGIORE', ''),
(180, 'RIVA LIGURE', ''),
(181, 'ROCCAVIGNALE', '9054'),
(182, 'ROCCHETTA DI VARA', ''),
(183, 'ROCCHETTA NERVINA', '8051'),
(184, 'RONCO SCRIVIA', ''),
(185, 'RONDANINA', ''),
(186, 'ROSSIGLIONE', ''),
(187, 'ROVEGNO', ''),
(188, 'SAN BARTOLOMEO AL MARE', '8052'),
(189, 'SAN BIAGIO DELLA CIMA', ''),
(190, 'SAN COLOMBANO CERTENOLI', ''),
(191, 'SAN LORENZO AL MARE', ''),
(192, 'SANREMO', '8055'),
(193, 'SANTA MARGHERITA LIGURE', '10054'),
(194, 'SANTO STEFANO AL MARE', ''),
(195, 'SANTO STEFANO D''AVETO', ''),
(196, 'SANTO STEFANO DI MAGRA', ''),
(197, 'SANT''OLCESE', ''),
(198, 'SARZANA', ''),
(199, 'SASSELLO', ''),
(200, 'SAVIGNONE', '10057'),
(201, 'SAVONA', ''),
(202, 'SEBORGA', ''),
(203, 'SERRA RICCO''', '10058'),
(204, 'SESTA GODANO', ''),
(205, 'SESTRI LEVANTE', '10059'),
(206, 'SOLDANO', ''),
(207, 'SORI', '10060'),
(208, 'SPOTORNO', '9057'),
(209, 'STELLA', '9058'),
(210, 'STELLANELLO', ''),
(211, 'TAGGIA', '8059'),
(212, 'TERZORIO', ''),
(213, 'TESTICO', ''),
(214, 'TIGLIETO', ''),
(215, 'TOIRANO', '9061'),
(216, 'TORRIGLIA', '10062'),
(217, 'TOVO SAN GIACOMO', '9062'),
(218, 'TRIBOGNA', ''),
(219, 'TRIORA', '8061'),
(220, 'URBE', ''),
(221, 'USCIO', ''),
(222, 'VADO LIGURE', '9064'),
(223, 'VALBREVENNA', '10065'),
(224, 'VALLEBONA', ''),
(225, 'VALLECROSIA', ''),
(226, 'VARAZZE', ''),
(227, 'VARESE LIGURE', '11029'),
(228, 'VASIA', ''),
(229, 'VENDONE', '9066'),
(230, 'VENTIMIGLIA', '8065'),
(231, 'VERNAZZA', '11030'),
(232, 'VESSALICO', ''),
(233, 'VEZZANO LIGURE', ''),
(234, 'VEZZI PORTIO', '9067'),
(235, 'VILLA FARALDI', ''),
(236, 'VILLANOVA D''ALBENGA', '9068'),
(237, 'VOBBIA', '10066'),
(238, 'ZIGNAGO', ''),
(239, 'ZOAGLI', ''),
(240, 'ZUCCARELLO', '9069');

-- --------------------------------------------------------

--
-- Struttura della tabella `DBCAVE`
--

DROP TABLE IF EXISTS `DBCAVE`;
CREATE TABLE IF NOT EXISTS `DBCAVE` (
  `ID` float NOT NULL,
  `REGIONE` varchar(255) NOT NULL,
  `PROV` varchar(255) NOT NULL,
  `NUM` float DEFAULT '0',
  `SPECIF` double DEFAULT '0',
  `DATAGG` date DEFAULT NULL,
  `DRILEV` date DEFAULT NULL,
  `NOME` varchar(255) DEFAULT NULL,
  `SINON` varchar(255) DEFAULT NULL,
  `RAS` varchar(255) DEFAULT NULL,
  `COMUNE` varchar(255) DEFAULT NULL,
  `LOCAL` varchar(255) DEFAULT NULL,
  `MONTE` varchar(255) NOT NULL,
  `VALLE` varchar(255) NOT NULL,
  `ACAR` varchar(255) DEFAULT NULL,
  `ACARCOD` varchar(255) DEFAULT NULL,
  `FM` varchar(255) DEFAULT NULL,
  `FMCOD` varchar(50) DEFAULT NULL,
  `AGE` varchar(255) DEFAULT NULL,
  `SVILRE` double DEFAULT '0',
  `SVILPLAN` double DEFAULT '0',
  `ESTEN` double DEFAULT '0',
  `DPOS` double DEFAULT '0',
  `DNEG` double DEFAULT '0',
  `DTOT` double DEFAULT '0',
  `TC_01` varchar(255) DEFAULT NULL,
  `latitude` varchar(128) DEFAULT NULL,
  `longitude` varchar(128) DEFAULT NULL,
  `DC_01` varchar(255) DEFAULT NULL,
  `SC_01` int(11) DEFAULT NULL,
  `AE_01` double DEFAULT '0',
  `QA_01` double DEFAULT '0',
  `QC_01` int(11) DEFAULT NULL,
  `VD_01` varchar(255) DEFAULT NULL,
  `IDRO` varchar(255) DEFAULT NULL,
  `RILEVATORI` varchar(255) DEFAULT NULL,
  `GRADORIL` varchar(255) DEFAULT NULL,
  `GRUPPI` text NOT NULL,
  `NOTE` text,
  `DATARIL` varchar(255) DEFAULT NULL,
  `NOTE2` longtext,
  `RCS` varchar(50) DEFAULT NULL,
  `DATAAGG` varchar(255) NOT NULL,
  `ITINERARIO` text NOT NULL,
  `DESCRIZIONE` text NOT NULL,
  `FAUNA` text NOT NULL,
  `POZZI` text NOT NULL,
  `PERCORRIBILITA` varchar(255) NOT NULL,
  `oricoordtype` varchar(255) NOT NULL,
  `recordinsert` datetime NOT NULL,
  `recordupdate` datetime NOT NULL,
  `username` varchar(80) NOT NULL,
  `userupdate` varchar(80) NOT NULL,
  `marina` varchar(3) NOT NULL,
  `archeologica` varchar(3) NOT NULL,
  `rischioambientale` varchar(3) NOT NULL,
  `chiusa` varchar(3) NOT NULL,
  `distrutta` varchar(3) NOT NULL,
  `noteversione` varchar(255) NOT NULL,
  `ANDAMENTO` varchar(255) NOT NULL,
  `verificata` varchar(1) NOT NULL,
  `data_verifica` date DEFAULT NULL,
  `hidden` varchar(255) NOT NULL,
  `photo1` varchar(255) NOT NULL,
  `groupview` varchar(255) NOT NULL,
  `CRONOLOGIA` text NOT NULL,
  `STORIA` text NOT NULL,
  `COLL` varchar(255) NOT NULL,
  `authorphoto1` varchar(255) NOT NULL,
  `authorDESCRIZIONE` varchar(255) NOT NULL,
  `authorITINERARIO` varchar(255) NOT NULL,
  `authorFAUNA` varchar(255) NOT NULL,
  `authorSTORIA` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Area carsica1` (`ACAR`),
  KEY `Comune` (`COMUNE`),
  KEY `Gruppo` (`GRUPPI`(333)),
  KEY `IDRO` (`IDRO`),
  KEY `Località` (`LOCAL`),
  KEY `Nome` (`NOME`),
  KEY `Numero` (`NUM`),
  KEY `Regione` (`REGIONE`),
  KEY `Rilevatori` (`RILEVATORI`),
  KEY `NUM` (`NUM`),
  KEY `PROV` (`PROV`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `DBCAVE`
--

INSERT INTO `DBCAVE` (`ID`, `REGIONE`, `PROV`, `NUM`, `SPECIF`, `DATAGG`, `DRILEV`, `NOME`, `SINON`, `RAS`, `COMUNE`, `LOCAL`, `MONTE`, `VALLE`, `ACAR`, `ACARCOD`, `FM`, `FMCOD`, `AGE`, `SVILRE`, `SVILPLAN`, `ESTEN`, `DPOS`, `DNEG`, `DTOT`, `TC_01`, `latitude`, `longitude`, `DC_01`, `SC_01`, `AE_01`, `QA_01`, `QC_01`, `VD_01`, `IDRO`, `RILEVATORI`, `GRADORIL`, `GRUPPI`, `NOTE`, `DATARIL`, `NOTE2`, `RCS`, `DATAAGG`, `ITINERARIO`, `DESCRIZIONE`, `FAUNA`, `POZZI`, `PERCORRIBILITA`, `oricoordtype`, `recordinsert`, `recordupdate`, `username`, `userupdate`, `marina`, `archeologica`, `rischioambientale`, `chiusa`, `distrutta`, `noteversione`, `ANDAMENTO`, `verificata`, `data_verifica`, `hidden`, `photo1`, `groupview`, `CRONOLOGIA`, `STORIA`, `COLL`, `authorphoto1`, `authorDESCRIZIONE`, `authorITINERARIO`, `authorFAUNA`, `authorSTORIA`) VALUES
(1, '', '', 1, NULL, NULL, NULL, 'TEST', NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'secca', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '2013-11-08 12:32:24', '2013-11-08 12:32:24', 'admin', 'admin', '', '', '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Struttura della tabella `DBCAVEUSER`
--

DROP TABLE IF EXISTS `DBCAVEUSER`;
CREATE TABLE IF NOT EXISTS `DBCAVEUSER` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `REGIONE` varchar(255) NOT NULL,
  `PROV` varchar(255) NOT NULL,
  `NUM` double DEFAULT '0',
  `SPECIF` double DEFAULT '0',
  `DATAGG` date DEFAULT NULL,
  `DRILEV` date DEFAULT NULL,
  `NOME` varchar(255) DEFAULT NULL,
  `SINON` varchar(255) DEFAULT NULL,
  `RAS` varchar(255) DEFAULT NULL,
  `COMUNE` varchar(255) DEFAULT NULL,
  `LOCAL` varchar(255) DEFAULT NULL,
  `MONTE` varchar(255) NOT NULL,
  `VALLE` varchar(255) NOT NULL,
  `ACAR` varchar(255) DEFAULT NULL,
  `ACARCOD` varchar(255) DEFAULT NULL,
  `FM` varchar(255) DEFAULT NULL,
  `FMCOD` varchar(50) DEFAULT NULL,
  `AGE` varchar(255) DEFAULT NULL,
  `SVILRE` double DEFAULT '0',
  `SVILPLAN` double DEFAULT '0',
  `ESTEN` double DEFAULT '0',
  `DPOS` double DEFAULT '0',
  `DNEG` double DEFAULT '0',
  `DTOT` double DEFAULT '0',
  `TC_01` varchar(255) DEFAULT NULL,
  `latitude` varchar(128) NOT NULL,
  `longitude` varchar(128) NOT NULL,
  `DC_01` varchar(255) DEFAULT NULL,
  `SC_01` int(11) DEFAULT NULL,
  `AE_01` double DEFAULT '0',
  `QA_01` double DEFAULT '0',
  `QC_01` int(11) DEFAULT NULL,
  `VD_01` varchar(255) DEFAULT NULL,
  `IDRO` varchar(255) DEFAULT NULL,
  `RILEVATORI` varchar(255) DEFAULT NULL,
  `GRADORIL` varchar(255) DEFAULT NULL,
  `GRUPPI` varchar(255) DEFAULT NULL,
  `NOTE` varchar(255) DEFAULT NULL,
  `DATARIL` varchar(255) DEFAULT NULL,
  `NOTE2` longtext,
  `RCS` varchar(50) DEFAULT NULL,
  `DATAAGG` varchar(255) NOT NULL,
  `ITINERARIO` text NOT NULL,
  `DESCRIZIONE` text NOT NULL,
  `FAUNA` varchar(255) NOT NULL,
  `oricoordtype` varchar(255) NOT NULL,
  `recordinsert` datetime NOT NULL,
  `recordupdate` datetime NOT NULL,
  `username` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `marina` varchar(3) NOT NULL,
  `archeologica` varchar(3) NOT NULL,
  `rischioambientale` varchar(3) NOT NULL,
  `chiusa` varchar(3) NOT NULL,
  `distrutta` varchar(3) NOT NULL,
  `recorddeleted` varchar(255) NOT NULL,
  `insert` varchar(255) NOT NULL,
  `update` varchar(255) NOT NULL,
  `ANDAMENTO` varchar(255) NOT NULL,
  `PERCORRIBILITA` varchar(255) NOT NULL,
  `POZZI` text NOT NULL,
  `photo1` varchar(255) NOT NULL,
  `photo2` varchar(255) NOT NULL,
  `photo3` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `2` (`PROV`),
  KEY `Area carsica1` (`ACAR`),
  KEY `Comune` (`COMUNE`),
  KEY `Gruppo` (`GRUPPI`),
  KEY `IDRO` (`IDRO`),
  KEY `Località` (`LOCAL`),
  KEY `Nome` (`NOME`),
  KEY `Numero` (`NUM`),
  KEY `Regione` (`REGIONE`),
  KEY `Rilevatori` (`RILEVATORI`),
  KEY `ID` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dump dei dati per la tabella `DBCAVEUSER`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `DBCAVE_versions`
--

DROP TABLE IF EXISTS `DBCAVE_versions`;
CREATE TABLE IF NOT EXISTS `DBCAVE_versions` (
  `idversions` varchar(255) NOT NULL,
  `ID` float NOT NULL,
  `REGIONE` varchar(2) NOT NULL,
  `PROV` varchar(2) NOT NULL,
  `NUM` varchar(10) NOT NULL,
  `SPECIF` varchar(10) NOT NULL,
  `DATAGG` datetime NOT NULL,
  `DRILEV` varchar(255) NOT NULL,
  `NOME` varchar(255) NOT NULL,
  `SINON` varchar(255) NOT NULL,
  `RAS` varchar(255) NOT NULL,
  `COMUNE` varchar(255) NOT NULL,
  `LOCAL` varchar(255) NOT NULL,
  `ACAR` varchar(255) NOT NULL,
  `ACARCOD` varchar(255) NOT NULL,
  `FM` varchar(255) NOT NULL,
  `FMCOD` varchar(255) NOT NULL,
  `AGE` varchar(255) NOT NULL,
  `SVILRE` varchar(255) NOT NULL,
  `SVILPLAN` varchar(255) NOT NULL,
  `ESTEN` varchar(255) NOT NULL,
  `DPOS` varchar(255) NOT NULL,
  `DNEG` varchar(255) NOT NULL,
  `DTOT` varchar(255) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `oricoordtype` varchar(255) NOT NULL,
  `TC_01` varchar(255) NOT NULL,
  `DC_01` varchar(255) NOT NULL,
  `SC_01` varchar(255) NOT NULL,
  `AE_01` varchar(255) NOT NULL,
  `QA_01` varchar(255) NOT NULL,
  `QC_01` varchar(255) NOT NULL,
  `LON_K_01` varchar(255) NOT NULL,
  `LAT_K_01` varchar(255) NOT NULL,
  `VD_01` varchar(255) NOT NULL,
  `IDRO` varchar(255) NOT NULL,
  `FAUNA` text NOT NULL,
  `RILEVATORI` varchar(255) NOT NULL,
  `GRADORIL` varchar(255) NOT NULL,
  `GRUPPI` text NOT NULL,
  `NOTE` text NOT NULL,
  `DESCRIZIONE` text NOT NULL,
  `ITINERARIO` text NOT NULL,
  `POZZI` text NOT NULL,
  `DATARIL` varchar(255) NOT NULL,
  `NOTE2` text NOT NULL,
  `RCS` varchar(255) NOT NULL,
  `DATAAGG` varchar(255) NOT NULL,
  `noteversione` varchar(255) NOT NULL,
  `recordinsert` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `MONTE` varchar(255) NOT NULL,
  `VALLE` varchar(255) NOT NULL,
  `PERCORRIBILITA` varchar(255) NOT NULL,
  `COLL` varchar(255) NOT NULL,
  PRIMARY KEY (`idversions`),
  KEY `NUM` (`NUM`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `DBCAVE_versions`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `DBFAUNA`
--

DROP TABLE IF EXISTS `DBFAUNA`;
CREATE TABLE IF NOT EXISTS `DBFAUNA` (
  `ID` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `scientific_name` varchar(255) NOT NULL,
  `order` varchar(255) NOT NULL,
  `family` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `photo1` varchar(255) NOT NULL,
  `recordinsert` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  `groupview` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `classe` varchar(255) NOT NULL,
  `genere` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `photo2` varchar(255) NOT NULL,
  `COLL` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `phylum` varchar(255) NOT NULL,
  `sinon` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `DBFAUNA`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `FAUNACAVE`
--

DROP TABLE IF EXISTS `FAUNACAVE`;
CREATE TABLE IF NOT EXISTS `FAUNACAVE` (
  `ID` varchar(255) NOT NULL,
  `NUMCAVE` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `scientific_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `photo1` varchar(255) NOT NULL,
  `photo2` varchar(255) NOT NULL,
  `DATE` varchar(255) NOT NULL,
  `caver` varchar(255) NOT NULL,
  `recordinsert` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  `groupview` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `ammount` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `FAUNACAVE`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `IDRO`
--

DROP TABLE IF EXISTS `IDRO`;
CREATE TABLE IF NOT EXISTS `IDRO` (
  `ID` varchar(255) NOT NULL,
  `IDRO` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `IDRO`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `LOCALITA`
--

DROP TABLE IF EXISTS `LOCALITA`;
CREATE TABLE IF NOT EXISTS `LOCALITA` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `LOCALITA` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ID` (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30 ;

--
-- Dump dei dati per la tabella `LOCALITA`
--

INSERT INTO `LOCALITA` (`ID`, `LOCALITA`) VALUES
(1, 'REBOCCO-CAVE'),
(2, 'PONTE DI NAVA'),
(3, 'MARELLAE'),
(4, 'MARCORELLA'),
(5, 'BAVARI'),
(6, 'FORTE BEGATO - RIGHI'),
(7, 'BORGORATTI-APPARIZIONE'),
(8, 'APPARIZIONE-OSTERIA DEL PAOLIN'),
(9, 'ISOVERDE'),
(10, 'GALLANETO'),
(11, 'SCAGLIA'),
(12, 'FORLANDOLI'),
(13, 'CRETO'),
(14, 'CREPPO'),
(15, 'BUGGIO'),
(16, 'PENDICI W CIMA ROVERE'),
(17, 'OLIVETTA'),
(18, 'PIAN MARINO'),
(19, 'MONTESORDO'),
(20, 'BRIC SCIMARCO'),
(21, 'S.ANTONINO DI PERTI'),
(22, 'EX-GALLERIA FERROVIARIA'),
(23, 'PUNTA DELLE GROTTE'),
(24, 'LE MANIE'),
(25, 'MONTECAPRAZZOPPA'),
(26, 'CAVE'),
(27, 'LA CAVA'),
(28, 'NERVI'),
(29, 'DOTTE/FRASCHERI');

-- --------------------------------------------------------

--
-- Struttura della tabella `PHOTOS`
--

DROP TABLE IF EXISTS `PHOTOS`;
CREATE TABLE IF NOT EXISTS `PHOTOS` (
  `ID` varchar(255) NOT NULL,
  `NUMCAVE` varchar(255) NOT NULL,
  `NAME` varchar(255) NOT NULL,
  `PHOTO` varchar(255) NOT NULL,
  `DATE` varchar(255) NOT NULL,
  `AUTHOR` varchar(255) NOT NULL,
  `LICENSE` varchar(255) NOT NULL,
  `DESC` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `PHOTOS`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `PROV`
--

DROP TABLE IF EXISTS `PROV`;
CREATE TABLE IF NOT EXISTS `PROV` (
  `ID` varchar(255) NOT NULL,
  `PROV` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `PROV`
--

INSERT INTO `PROV` (`ID`, `PROV`) VALUES
('1', 'GE'),
('2', 'IM'),
('3', 'SV'),
('4', 'SP');

-- --------------------------------------------------------

--
-- Struttura della tabella `REGION`
--

DROP TABLE IF EXISTS `REGION`;
CREATE TABLE IF NOT EXISTS `REGION` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `REGNAM` varchar(255) DEFAULT NULL,
  `REGIONE` varchar(255) DEFAULT NULL,
  `PVCOD` varchar(255) DEFAULT NULL,
  `PROV` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `REGION2CODREG` (`REGIONE`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=97 ;

--
-- Dump dei dati per la tabella `REGION`
--

INSERT INTO `REGION` (`ID`, `REGNAM`, `REGIONE`, `PVCOD`, `PROV`) VALUES
(1, 'Valle d''Aosta', 'VA', 'AO', 'Aosta'),
(2, 'Piemonte', 'PI', 'TO', 'Torino'),
(3, 'Piemonte', 'PI', 'CN', 'Cuneo'),
(4, 'Piemonte', 'PI', 'VC', 'Vercelli'),
(5, 'Piemonte', 'PI', 'NO', 'Novara'),
(6, 'Piemonte', 'PI', 'AL', 'Alessandria'),
(7, 'Piemonte', 'PI', 'AT', 'Asti'),
(8, 'Piemonte', 'PI', 'BI', 'Biella'),
(9, 'Lombardia', 'LO', 'MI', 'Milano'),
(10, 'Lombardia', 'LO', 'VA', 'Varese'),
(11, 'Lombardia', 'LO', 'CO', 'Como'),
(12, 'Lombardia', 'LO', 'LC', 'Lecco'),
(13, 'Lombardia', 'LO', 'SO', 'Sondrio'),
(14, 'Lombardia', 'LO', 'BG', 'Bergamo'),
(15, 'Lombardia', 'LO', 'BS', 'Brescia'),
(16, 'Lombardia', 'LO', 'CR', 'Cremona'),
(17, 'Lombardia', 'LO', 'PV', 'Pavia'),
(18, 'Lombardia', 'LO', 'MN', 'Mantova'),
(19, 'Trentino Alto Adige', 'TA', 'BZ', 'Bolzano'),
(20, 'Trentino Alto Adige', 'TA', 'TN', 'Trento'),
(21, 'Friuli Venezia Giulia', 'VG', 'GO', 'Gorizia'),
(22, 'Friuli Venezia Giulia', 'VG', 'TS', 'Trieste'),
(23, 'Friuli Venezia Giulia', 'VG', 'UD', 'Udine'),
(24, 'Friuli Venezia Giulia', 'VG', 'PN', 'Pordenone'),
(25, 'Veneto', 'VE', 'BL', 'Belluno'),
(26, 'Veneto', 'VE', 'PD', 'Padova'),
(27, 'Veneto', 'VE', 'RO', 'Rovigo'),
(28, 'Veneto', 'VE', 'TV', 'Treviso'),
(29, 'Veneto', 'VE', 'VE', 'Venezia'),
(30, 'Veneto', 'VE', 'VR', 'Verona'),
(31, 'Veneto', 'VE', 'VI', 'Vicenza'),
(32, 'Liguria', 'LI', 'GE', 'Genova'),
(33, 'Liguria', 'LI', 'IM', 'Imperia'),
(34, 'Liguria', 'LI', 'SP', 'La Spezia'),
(35, 'Liguria', 'LI', 'SV', 'Savona'),
(36, 'Emilia Romagna', 'ER', 'BO', 'Bologna'),
(37, 'Emilia Romagna', 'ER', 'FE', 'Ferrara'),
(38, 'Emilia Romagna', 'ER', 'FO', 'Forlì'),
(39, 'Emilia Romagna', 'ER', 'CS', 'Cesena'),
(40, 'Emilia Romagna', 'ER', 'MO', 'Modena'),
(41, 'Emilia Romagna', 'ER', 'PR', 'Parma'),
(42, 'Emilia Romagna', 'ER', 'PC', 'Piacenza'),
(43, 'Emilia Romagna', 'ER', 'RA', 'Ravenna'),
(44, 'Emilia Romagna', 'ER', 'RE', 'Reggio Emilia'),
(45, 'Emilia Romagna', 'ER', 'RN', 'Rimini'),
(46, 'Toscana', 'TO', 'AR', 'Arezzo'),
(47, 'Toscana', 'TO', 'FI', 'Firenze'),
(48, 'Toscana', 'TO', 'GR', 'Grosseto'),
(49, 'Toscana', 'TO', 'LI', 'Livorno'),
(50, 'Toscana', 'TO', 'LU', 'Lucca'),
(51, 'Toscana', 'TO', 'MS', 'Massa Carrara'),
(52, 'Toscana', 'TO', 'PI', 'Pisa'),
(53, 'Toscana', 'TO', 'PS', 'Pistoia'),
(54, 'Toscana', 'TO', 'PT', 'Prato'),
(55, 'Toscana', 'TO', 'SI', 'Siena'),
(56, 'Marche', 'MA', 'AN', 'Ancona'),
(57, 'Marche', 'MA', 'AP', 'Ascoli Piceno'),
(58, 'Marche', 'MA', 'MC', 'Macerata'),
(59, 'Marche', 'MA', 'PE', 'Pesaro'),
(60, 'Umbria', 'UM', 'PG', 'Perugia'),
(61, 'Umbria', 'UM', 'TR', 'Terni'),
(62, 'Lazio', 'LA', 'FR', 'Frosinone'),
(63, 'Lazio', 'LA', 'LT', 'Latina'),
(64, 'Lazio', 'LA', 'RI', 'Rieti'),
(65, 'Lazio', 'LA', 'ROMA', 'Roma'),
(66, 'Lazio', 'LA', 'VT', 'Viterbo'),
(67, 'Abruzzo', 'AB', 'CH', 'Chieti'),
(68, 'Abruzzo', 'AB', 'AQ', 'L''Aquila'),
(69, 'Abruzzo', 'AB', 'PS', 'Pescara'),
(70, 'Abruzzo', 'AB', 'TE', 'Teramo'),
(71, 'Molise', 'MO', 'CB', 'Campobasso'),
(72, 'Basilicata', 'BA', 'MT', 'Matera'),
(73, 'Basilicata', 'BA', 'PZ', 'Potenza'),
(74, 'Puglie', 'PU', 'BA', 'Bari'),
(75, 'Puglie', 'PU', 'FG', 'Foggia'),
(76, 'Puglie', 'PU', 'LE', 'Lecce'),
(77, 'Puglie', 'PU', 'TA', 'Taranto'),
(78, 'Calabria', 'CL', 'RC', 'Reggio Calabria'),
(79, 'Calabria', 'CL', 'CS', 'Cosenza'),
(80, 'Calabria', 'CL', 'CZ', 'Catanzaro'),
(81, 'Calabria', 'CL', 'VV', 'Vibo Valentia'),
(82, 'Campania', 'CM', 'NA', 'Napoli'),
(83, 'Campania', 'CM', 'SA', 'Salerno'),
(84, 'Campania', 'CM', 'CE', 'Caserta'),
(85, 'Campania', 'CM', 'AV', 'Avellino'),
(86, 'Sicilia', 'SI', 'AG', 'Agrigento'),
(87, 'Sicilia', 'SI', 'CT', 'Catania'),
(88, 'Sicilia', 'SI', 'ME', 'Messina'),
(89, 'Sicilia', 'SI', 'PA', 'Palermo'),
(90, 'Sicilia', 'SI', 'RG', 'Ragusa'),
(91, 'Sicilia', 'SI', 'SC', 'Siracusa'),
(92, 'Sicilia', 'SI', 'TP', 'Trapani'),
(93, 'Sardegna', 'SA', 'CA', 'Cagliari'),
(94, 'Sardegna', 'SA', 'NU', 'Nuoro'),
(95, 'Sardegna', 'SA', 'OR', 'Oristano'),
(96, 'Sardegna', 'SA', 'SS', 'Sassari');

-- --------------------------------------------------------

--
-- Struttura della tabella `SPRINGS`
--

DROP TABLE IF EXISTS `SPRINGS`;
CREATE TABLE IF NOT EXISTS `SPRINGS` (
  `ID` varchar(255) NOT NULL,
  `NUM` varchar(255) NOT NULL,
  `NOME` varchar(255) NOT NULL,
  `SINON` varchar(255) NOT NULL,
  `SPECIF` varchar(255) NOT NULL,
  `DATAGG` varchar(255) NOT NULL,
  `REGIONE` varchar(255) NOT NULL,
  `PROV` varchar(255) NOT NULL,
  `COMUNE` varchar(255) NOT NULL,
  `LOCAL` varchar(255) NOT NULL,
  `MONTE` varchar(255) NOT NULL,
  `VALLE` varchar(255) NOT NULL,
  `ACARCOD` varchar(255) NOT NULL,
  `FMCOD` varchar(255) NOT NULL,
  `AGE` varchar(255) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `TC_01` varchar(255) NOT NULL,
  `AE_01` varchar(255) NOT NULL,
  `DC_01` text NOT NULL,
  `QA_01` varchar(255) NOT NULL,
  `QC_01` varchar(255) NOT NULL,
  `VD_01` varchar(255) NOT NULL,
  `DESCRIZIONE` text NOT NULL,
  `ITINERARIO` text NOT NULL,
  `recordinsert` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `userupdate` varchar(255) NOT NULL,
  `recordupdate` varchar(255) NOT NULL,
  `NUMCAVE` varchar(255) NOT NULL,
  `photo1` varchar(255) NOT NULL,
  `QMASSIMA` varchar(255) NOT NULL,
  `QMINIMA` varchar(255) NOT NULL,
  `QMEDIA` varchar(255) NOT NULL,
  `UTILIZZO` varchar(255) NOT NULL,
  `USO` varchar(255) NOT NULL,
  `PRELIEVO` varchar(255) NOT NULL,
  `UTENTE` varchar(255) NOT NULL,
  `AR_RISPETTO` varchar(255) NOT NULL,
  `IDROLOGIA` varchar(255) NOT NULL,
  `IDROGEOL` varchar(255) NOT NULL,
  `MORFOLOGIA` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `BIBLIOQMASSIMA` varchar(255) NOT NULL,
  `BIBLIOQMINIMA` varchar(255) NOT NULL,
  `BIBLIOQMEDIA` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `SPRINGS`
--


-- --------------------------------------------------------

--
-- Struttura della tabella `TGEO`
--

DROP TABLE IF EXISTS `TGEO`;
CREATE TABLE IF NOT EXISTS `TGEO` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `FMCOD` varchar(255) DEFAULT NULL,
  `FM` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=107 ;

--
-- Dump dei dati per la tabella `TGEO`
--

INSERT INTO `TGEO` (`ID`, `FMCOD`, `FM`) VALUES
(1, 'ABE', 'ALBERESE'),
(2, 'ALE', 'GRUPPO DELL''ALBERESE'),
(3, 'ALN', 'FORMAZIONE DI ALBENGA'),
(4, 'ALS', 'GNEISS DI ALBISOLA'),
(5, 'ANT', 'FORMAZIONE DI ANTOGNOLA'),
(6, 'APA', 'ARGILLE A PALOMBINI'),
(7, 'ARN', 'RADIOLARITI DI ARNASCO'),
(8, 'ARZ', 'CRISTALLINO DI ARENZANO'),
(9, 'AVE', 'FORMAZIONE DELLA VAL D''AVETO'),
(10, 'BEI', 'OFIOLITI DEL MONTE BEIGUA'),
(11, 'CAO', 'CALCARI DI MONTE CAIO'),
(12, 'CCA', 'CALCARE CAVERNOSO'),
(13, 'CMV', 'CONGLOMERATI DI MONTE VILLA'),
(14, 'COG', 'DOLOMIE DI COGOLEDO'),
(15, 'CRA', 'BRECCE DELLA COSTA DI CRAVARA'),
(16, 'CSU', 'ARENARIE DI CASANOVA'),
(17, 'CVE', 'CALCARE DI VERZI'),
(18, 'DMA', 'DOLOMIE DI MONTE ARENA'),
(19, 'DPR', 'DOLOMIA PRINCIPALE'),
(20, 'DSD', 'FORMAZIONE DEI DIASPRI'),
(21, 'DSG', 'DIASPRI, SCISTI DIASPRIGNI'),
(22, 'DUG', 'DOLOMIE VACUOLARI E GESSI'),
(23, 'ELM', 'FLYSCH AD ELMINTOIDI'),
(24, 'EZE', 'FORMAZIONE DI EZE'),
(25, 'FAN', 'FORMAZIONE DEL MONTE ANTOLA'),
(26, 'FGA', 'FORMAZIONE DEI GALESTRI'),
(27, 'FII', 'COMPLESSO DI BASE DEL CALCARE DI FINALE LIGURE'),
(28, 'FIN', 'CALCARE DI FINALE LIGURE'),
(29, 'FLI', 'CALCARI DI FIGLINE'),
(30, 'FMU', 'FORMAZIONE DI MURIALDO'),
(31, 'GIC', 'ARGILLE A PALOMBINI DEL LAGO DI GIACOPIANE'),
(32, 'GLL', 'CALCARI DI GALLANETO'),
(33, 'GRR', 'SCISTI DI GORRA'),
(34, 'GSA', 'GRANITI DI SANDA'),
(35, 'GTT', 'ARGILLITI DI GIARIETTE'),
(36, 'IND', 'INDIFFERENZIATO'),
(37, 'ISO', 'DOLOMIA DI ISOVERDE'),
(38, 'LET', 'GRANITI DEL TORRENTE LETIMBRO'),
(39, 'LLV', 'LEMBO DI CELLE LIGURE-VARAZZE'),
(40, 'LSG', 'LEMBO DI SANTA GIUSTINA'),
(41, 'LVG', 'FORMAZIONE DI VAL LAVAGNA'),
(42, 'MAC', 'MACIGNO'),
(43, 'MCH', 'MACIGNO DEL CHIANTI'),
(44, 'MGG', 'ARGILLITI DI MONTOGGIO'),
(45, 'MGL', 'BRECCE DI MONTE GALLERO'),
(46, 'MIG', 'ARGILLITI DI MIGNANEGO'),
(47, 'MEO', 'CALCARI DI MENOSIO'),
(48, 'MOE', 'FORMAZIONE DI MONESIGLIO'),
(49, 'MOG', 'PELITI DI MOGLIO'),
(50, 'MOR', 'FORMAZIONE DI MOLARE'),
(51, 'MVE', 'ARGILLE A PALOMBINI DI MONTE VERI'),
(52, 'MTE', 'ARGILLITI DI MONTANESI'),
(53, 'MTG', 'FORMAZIONE DI MONTOGGIO'),
(54, 'MUG', 'MACIGNO DEL MUGELLO'),
(55, 'NOT', 'SERIE DI MONTENOTTE'),
(56, 'NUC', 'MIGMATITI DI NUCETTO'),
(57, 'NAV', 'CALCARI DI RIO DI NAVA'),
(58, 'ORV', 'ARGILLE DI ORTOVERO'),
(59, 'OSI', 'PORFIDI DI OSIGLIA'),
(60, 'OF', 'OFICALCI'),
(61, 'OLL', 'FORMAZIONE DI OLLANO'),
(62, 'OTO', 'CALCARI DI OTTONE'),
(63, 'PAB', 'ARGILLE A PALOMBINI DEL PASSO DELLA BOCCHETTA'),
(64, 'PAG', 'ARGILLITI DI PAGLIARO'),
(65, 'PEN', 'FORMAZIONE DI MONTE PENICE'),
(66, 'PDM', 'PORFIROIDI DEL MELOGNO'),
(67, 'PNS', 'FORMAZIONE DI MONTE PIANOSA'),
(68, 'POR', 'CONGLOMERATO DI PORTOFINO'),
(69, 'QFA', 'QUARZITI DI FOSSO ANGASSINO'),
(70, 'QMB', 'QUARZITI DI MONTE BIGNONE'),
(71, 'QPN', 'QUARZITI DI PONTE DI NAVA'),
(72, 'RCC', 'FORMAZIONE DI ROCCHETTA'),
(73, 'RIO', 'MARNE DI RIGOROSO'),
(74, 'ROC', 'FORMAZIONE DI RONCO'),
(75, 'RPR', 'DOLOMIA DI ROCCA PRIONE'),
(76, 'SAS', 'FORMAZIONE DEL SANTUARIO DI SAVONA'),
(77, 'SAV', ' CONGLOMERATI DI SAVIGNONE'),
(78, 'SBA', 'FORMAZIONE DI SAN BARTOLOMEO'),
(79, 'SOP', 'MARNE DI SOPRALACROCE'),
(80, 'SOT', 'CALCARI DI MONTE SOTTA'),
(81, 'SPM', 'DOLOMIE DI SAN PIETRO DEI MONTI'),
(82, 'SPN', 'ANFIBIOLITI DI MONTE SPINARDA'),
(83, 'SPP', 'SCAGLIA APPENNINICA'),
(84, 'SSS', 'LEMBO DI SASSELLO'),
(85, 'STO', 'SCAGLIA TOSCANA'),
(86, 'TAR', 'CALCARI DI VAL TANARELLO'),
(87, 'TES', 'FORMAZIONE DI TESTICO'),
(88, 'TOB', 'FORMAZIONE DI TORBI'),
(89, 'TUR', 'CALCESCISTI DEL TURCHINO'),
(90, 'UBA', 'CALCARI DI UBAGA'),
(91, 'VBR', 'VERRUCANO BRIANZONESE'),
(92, 'VEV', 'CALCARI DI VERAVO'),
(93, 'VLT', 'GRUPPO DEI CALCESCISTI CON PIETRE VERDI DI VOLTRI'),
(94, 'VRR', 'VERRUCANO APPENNINICO'),
(95, 'VSC', 'ARENARIE DI VAL SCRIVIA'),
(96, 'ZAT', 'ARENARIE DI MONTE ZATTA'),
(102, 'CAL', 'CALCARI A CALPIONELLE'),
(103, 'FLY', 'FLYISCH DEL MONTE ANTOLA'),
(104, 'LIV', 'CALCARI DI ROCCA LIVERNA'),
(105, 'NUM', 'CALCARI A NUMMULITI'),
(106, 'PDF', 'PIETRA DI FINALE');

-- --------------------------------------------------------

--
-- Struttura della tabella `TIPOC`
--

DROP TABLE IF EXISTS `TIPOC`;
CREATE TABLE IF NOT EXISTS `TIPOC` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TC_00` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `datum` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ID` (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39 ;

--
-- Dump dei dati per la tabella `TIPOC`
--

INSERT INTO `TIPOC` (`ID`, `TC_00`, `description`, `datum`) VALUES
(1, 'IGM 1:25000 Geografiche M.Mario (EST)', '', 'ROME1940'),
(2, 'IGM 1:25000 Geografiche M.Mario (OVEST)', '', 'ROME1940'),
(3, 'IGM 1:25000 UTM', '', ''),
(4, 'IGM 1:25000 Gauss Boaga', '', ''),
(5, 'IGM 1:25000 Geografiche ED50', '', 'ED50'),
(6, 'IGM 1:50000 UTM', '', ''),
(7, 'IGM 1:50000 Gauss Boaga', '', ''),
(8, 'IGM 1:50000 Geografiche ED50', '', 'ED50'),
(9, 'CTR 1:10000 Gauss Boaga', '', ''),
(10, 'CTR 1:10000 UTM', '', ''),
(11, 'CTR 1:10000 Geografiche M.Mario (EST)', '', 'ROME1940'),
(12, 'CTR 1:10000 Geografiche ED50', '', 'ED50'),
(13, 'CTR 1:5000  Gauss Boaga', '', ''),
(14, 'CTR 1:5000  UTM', '', ''),
(15, 'CTR 1:5000 Geografiche M.Mario (EST)', '', 'ROME1940'),
(16, 'CTR 1:5000  Geografiche ED50', '', 'ED50'),
(17, 'CNS 1:25000 UTM', '', ''),
(18, 'CNS 1:25000 Geografiche', '', ''),
(19, 'CNS 1:50000 UTM', '', ''),
(20, 'CNS 1:50000 Geografiche', '', ''),
(21, 'TCI 1:20000 Geografiche', '', ''),
(22, 'GGM 1:1000 Geografiche', '', ''),
(23, 'CTR 1:10000 Geografiche M.Mario (OVEST)', '', 'ROME1940'),
(24, 'CTR 1:5000 Geografiche M.Mario (OVEST)', '', 'ROME1940'),
(25, 'CTR 1:25000 Geografiche ED50', '', 'ED50'),
(26, 'CR 1:25000 Geografiche Gauss Boaga', '', ''),
(27, 'CR 1:25000 Geografiche ED50', '', 'ED50'),
(28, 'GPS Geografiche WGS84', '', 'WGS84'),
(29, 'GPS UTM WGS84', '', 'WGS84'),
(30, 'GPS Geografiche ED50', '', 'ED50'),
(31, 'GPS UTM ED50', '', 'ED50'),
(32, 'ED50 Generiche', 'Coordinate generiche in ED50', 'ED50'),
(33, 'WGS84 Generiche', 'Coordinate generiche in ED50', 'WGS84'),
(34, 'ROME1940 Generiche M.Mario', '', 'ROME1940'),
(35, 'UTM ED50 Generiche', '', 'ED50'),
(36, 'UTM WGS84 Generiche', '', 'WGS84'),
(37, 'GPS Geografiche ROME1940', 'GPS con datum su rome1940', 'ROME1940'),
(38, 'Gauss Boaga Generiche', '', 'ROME1940');
