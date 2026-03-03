-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2026 at 09:09 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `melody_masters_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `type` enum('physical','digital') NOT NULL DEFAULT 'physical'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `type`) VALUES
(1, 'Guitars', NULL, 'physical'),
(2, 'Keyboards', NULL, 'physical'),
(3, 'Drums', NULL, 'physical'),
(4, 'Cymbals', NULL, 'physical'),
(5, 'Violins', NULL, 'physical'),
(6, 'Flutes', NULL, 'physical'),
(7, 'Accessories', NULL, 'physical'),
(16, 'Audio Packs', NULL, 'digital'),
(17, 'Music Sheets', NULL, 'digital'),
(18, 'Learn Music', NULL, 'digital');

-- --------------------------------------------------------

--
-- Table structure for table `digital_products`
--

CREATE TABLE `digital_products` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `download_limit` int(11) DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `digital_products`
--

INSERT INTO `digital_products` (`id`, `product_id`, `file_path`, `download_limit`) VALUES
(8, 32, 'dl_69a2df8490a55.zip', 20),
(9, 33, 'dl_69a2dfcc5eef3.zip', 50),
(10, 34, 'dl_69a2e00987f34.zip', 50),
(11, 35, 'dl_69a2e36b9bf7e.pdf', 20),
(12, 36, 'dl_69a2e3b9bccaa.pdf', 20),
(13, 37, 'dl_69a2e3da0a8ed.pdf', 50),
(14, 38, 'dl_69a2e4d83ee91.pdf', 50),
(15, 39, 'dl_69a2e55f4003c.pdf', 50),
(16, 40, 'dl_69a2e639dbd4b.pdf', 50);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address1` varchar(180) DEFAULT NULL,
  `address2` varchar(180) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `payment_method` varchar(40) DEFAULT 'cod',
  `notes` text,
  `total` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `full_name`, `email`, `phone`, `address1`, `address2`, `city`, `postcode`, `country`, `payment_method`, `notes`, `total`, `shipping_cost`, `status`, `created_at`) VALUES
(29, 4, 'Malsha', 'malsha@gmail.com', '0771234567', '123 street, badulla rd', '', 'Bandarawela', '23', 'United Kingdom', 'cod', '', '699.99', '0.00', 'Processing', '2026-03-03 08:00:08'),
(30, 7, 'inuka', 'inuka@gmail.com', '0715252525', '1/23, Lake Round rd', '', 'Kurunegala', '90021', 'Sri Lanka', 'card', 'meet at the gate', '1349.00', '0.00', 'Shipped', '2026-03-03 08:01:50'),
(31, 6, 'mathsara', 'mathsara@gmail.com', '0759898989', '99/1, Temple street', '', 'Mahiyanganaya', '20054', 'Sri Lanka', 'cod', 'call before deliver', '88.99', '8.99', 'Delivered', '2026-03-03 08:03:16'),
(32, 2, 'supun', 'supun@gmail.com', '0111111111', 'chandrika Kumarathunga rd, malabe', '', 'Malabe', '00125', 'Sri Lanka', 'cod', 'call before deliver', '17.98', '8.99', 'Pending', '2026-03-03 08:05:31'),
(33, 1, 'Dilmin Ekanayaka', 'dilmin@gmail.com', '0766565656', 'golden park, mirissa rd', '', 'Matara', '19990', 'Sri Lanka', 'card', 'meet at the door', '699.00', '0.00', 'Cancelled', '2026-03-03 08:07:04'),
(34, 1, 'Dilmin Ekanayaka', 'dilmin@gmail.com', '0759898989', 'golden park, mirissa rd', '', 'Matara', '19990', 'Sri Lanka', 'card', '', '88.99', '8.99', 'Delivered', '2026-03-03 08:08:37');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `download_count` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `download_count`) VALUES
(32, 29, 1, 1, '699.99', 0),
(33, 30, 7, 1, '1349.00', 0),
(34, 31, 36, 1, '80.00', 0),
(35, 32, 19, 1, '8.99', 0),
(36, 33, 10, 1, '699.00', 0),
(37, 34, 37, 1, '80.00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `stock` int(11) DEFAULT '0',
  `type` enum('physical','digital') NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `price`, `shipping_cost`, `stock`, `type`, `image`, `description`, `created_at`) VALUES
(1, 1, 'Fender Player Stratocaster', '699.99', '0.00', 10, 'physical', 'guitar02.png', 'The Player Stratocaster brings classic Fender tone and feel to a modern market. Features an alder body, maple neck, and three Player Series Alnico 5 Strat single-coil pickups for bright, clear sound perfect for any style.', '2026-02-22 18:24:52'),
(2, 1, 'Gibson Les Paul Standard 50s', '2499.00', '0.00', 3, 'physical', 'guitar03.png', 'Inspired by the legendary 1950s originals, this Les Paul Standard delivers warm, fat tones from its Burstbucker 1 & 2 pickups. Mahogany body with maple cap, slim taper neck profile, and Kluson-style tuners.', '2026-02-22 18:24:52'),
(3, 1, 'Epiphone Casino Hollow Body', '599.00', '0.00', 10, 'physical', 'guitar04.png', 'Favoured by the Beatles and countless blues legends, the Casino is a fully hollow thinline electric. P-90 style single-coil Dogear pickups deliver a sharp, cutting mid-range bite that cuts through any mix.', '2026-02-22 18:24:52'),
(4, 2, 'Nord Stage 4 Compact', '2799.00', '0.00', 4, 'physical', 'keyboard01.png', 'The Nord Stage 4 Compact is a world-class performance keyboard featuring the renowned Nord Piano 5 Engine, Nord Lead A1 Synth, and Nord C2D Organ section. Seamless transitions, powerful effects, and 73 semi-weighted keys.', '2026-02-22 18:24:52'),
(5, 2, 'Roland FP-90X Digital Piano', '1699.00', '0.00', 8, 'physical', 'keyboard02.png', 'Rolands finest portable piano. The FP-90X features the acclaimed PHA-50 keyboard with ivory feel and escapement, SuperNATURAL Piano Modeling, and 88 weighted keys — the most authentic piano experience outside of a grand.', '2026-02-22 18:24:52'),
(6, 3, 'DW Performance Series Kit', '2199.00', '0.00', 4, 'physical', 'drum01.png', 'DW\'s Performance Series drum kit delivers professional quality at a serious price point. Poplar shells with a 7-ply formula offer a full, focused tone with excellent projection. Includes 10\" tom, 12\" tom, 14\" floor tom, and 22\" kick.', '2026-02-22 18:24:52'),
(7, 3, 'Pearl Session Studio Select', '1349.00', '0.00', 5, 'physical', 'drum02.png', 'Crafted with a 6-ply Birch/Basswood shell formula, the Session Studio Select produces a warm, well-rounded tone with enhanced attack. Ideal for studio sessions and live performances that demand consistent quality.', '2026-02-22 18:24:52'),
(8, 3, 'Tama Starclassic Walnut/Birch', '1899.00', '0.00', 1, 'physical', 'drum03.png', 'Walnut outer plies married with birch inner plies create a uniquely warm, complex, and well-defined tone. The Starclassic Walnut/Birch is Tama\'s premium offering for the discerning drummer.', '2026-02-22 18:24:52'),
(9, 3, 'Ludwig Classic Maple Kit', '2599.00', '0.00', 4, 'physical', 'drum04.png', 'The drum kit that defined rock and roll. Ludwig\'s Classic Maple shells have the same construction used on recordings that shaped popular music. Six-ply maple shells, chrome hardware, and a timeless look.', '2026-02-22 18:24:52'),
(10, 4, 'Zildjian A Custom Cymbal Set', '699.00', '0.00', 7, 'physical', 'Cymbol01.png', 'The A Custom line offers brilliant finish cymbals with a focused, cutting sound. This set includes 14\" hi-hats, 16\" crash, 18\" crash, and 20\" medium ride — perfect for rock, pop, and studio work.', '2026-02-22 18:24:52'),
(11, 4, 'Sabian HHX Evolution Set', '849.00', '0.00', 5, 'physical', 'Cymbol02.png', 'Sabian\'s HHX Evolution series delivers modern, complex tones with an explosive character. Brilliant finish with hand-hammering that creates a raw, earthy feel alongside a brilliant shimmer. Includes hi-hat, crash, and ride.', '2026-02-22 18:24:52'),
(12, 5, 'Stentor Student II Violin 4/4', '149.00', '0.00', 19, 'physical', 'violin01.png', 'The most popular student violin in the world. The Stentor Student II features a solid carved tonewood top, well-shaped bow, rosin, and a lightweight case. An ideal first instrument for aspiring string players.', '2026-02-22 18:24:52'),
(13, 5, 'Yamaha V20G Intermediate Violin', '499.00', '0.00', 9, 'physical', 'violin02.png', 'Stepping up from beginner instruments, the V20G features a hand-carved spruce top and maple back and sides, with a distinctive reddish-brown finish. A quality bow and lightweight case are included.', '2026-02-22 18:24:52'),
(14, 5, 'Mendini MV500 Ebony Violin', '229.00', '0.00', 14, 'physical', 'violin03.png', 'The MV500 is fitted with all-ebony fittings — pegs, fingerboard, tailpiece, and chinrest — for a more refined aesthetic and sound. The solid spruce top and maple sides produce a clear, resonant tone.', '2026-02-22 18:24:52'),
(15, 6, 'Yamaha YFL-222 Student Flute', '299.00', '0.00', 15, 'physical', 'flute01.png', 'The YFL-222 is a key choice for beginners and school players. Its silver-plated body and precise key mechanism make tone production effortless. The pointed arm G key provides a natural and secure playing position.', '2026-02-22 18:24:52'),
(16, 6, 'Jupiter JFL700A Intermediate Flute', '599.00', '0.00', 8, 'physical', 'flute02.png', 'Designed for advancing students, the JFL700A features a solid sterling silver head joint that significantly improves response, tone colour, and projection. Offset G key and split E mechanism for added ease of play.', '2026-02-22 18:24:52'),
(17, 7, 'Vic Firth American Classic 5A Drumsticks', '12.99', '0.00', 100, 'physical', 'Acce-BrownDrumStick.png', 'The world\'s best-selling drumstick. Hickory construction with nylon tip for a brighter sound and longer life. Perfect for any style at any volume. Standard 5A dimensions for versatile, balanced playing.', '2026-02-22 18:24:52'),
(18, 7, 'Elixir Nanoweb Guitar Strings 10-46', '14.99', '0.00', 80, 'physical', 'Acce-GuitarStrings01.png', 'Elixir\'s NANOWEB coating surrounds the entire string — not just the windings — delivering an extended lifespan without sacrificing bright tone. A favourite of touring and recording guitarists worldwide.', '2026-02-22 18:24:52'),
(19, 7, 'D\'Addario EXL110 Nickel Wound Set', '8.99', '0.00', 118, 'physical', 'Acce-GuitarStrings02.png', 'The most popular string set in the world. Nickel wound with a plain steel high E and B. Regular 10-46 gauge suits any playing style from light fingerpicking to heavy strumming. Consistent quality every time.', '2026-02-22 18:24:52'),
(20, 7, 'Vic Firth Stick Bag SBAG2', '29.99', '0.00', 40, 'physical', 'Best stick bag Vic Firth SBAG2.png', 'Professional quality stick bag featuring 8 stick pockets, 2 accessory pockets, and quick-release shoulder strap. Made from durable polyester with reinforced stitching — holds everything a working drummer needs.', '2026-02-22 18:24:52'),
(21, 7, 'Mike Portnoy Percussion Kit', '349.00', '0.00', 9, 'physical', 'Acce-MikePortnoy PercussionKit.png', 'Designed in collaboration with Dream Theater\'s Mike Portnoy, this signature percussion kit includes a range of exotic percussion instruments used on his legendary recordings. A unique collector\'s piece for the serious percussionist.', '2026-02-22 18:24:52'),
(22, 7, 'ROC-N-SOC Nitro Drum Throne', '149.00', '0.00', 1, 'physical', 'Acce-ROC-N-SOC Nitro.png', 'The ROC-N-SOC Nitro is the industry-standard drum throne for professional drummers. Gas-spring height adjustment with a locking collar, round cushioned top, and solid round base provide comfort and stability for any length of performance.', '2026-02-22 18:24:52'),
(32, 16, 'EchoFlowâ„¢ | Royalty-Free Music Collection', '50.00', '0.00', 999999, 'digital', 'prod_69a2df8490e31.jpg', 'High-quality cinematic & modern tracks for videos, ads, podcasts, and games.\r\n100% copyright-safe, instant download, ready to use.', '2026-02-28 12:28:52'),
(33, 16, 'PulseForgeâ„¢ | Beat Sample Pack', '50.00', '0.00', 999999, 'digital', 'prod_69a2dfcc718ea.jpg', 'Hard-hitting drums, punchy kicks, and clean snares for modern producers.\r\nPerfect for hip-hop, trap, and electronic music.', '2026-02-28 12:30:04'),
(34, 16, 'LoopScapeâ„¢ | Creative Loop Pack', '50.00', '0.00', 999999, 'digital', 'prod_69a2e00988322.jpg', 'Melodic, drum, and bass loops designed to boost creativity and speed up workflow.\r\nSeamless, flexible, and DAW-ready.', '2026-02-28 12:31:05'),
(35, 18, 'TheoryUnlockedâ„¢ | Music Theory eBooks', '80.00', '0.00', 999999, 'digital', 'prod_69a2e36b9c628.jpg', 'TheoryUnlockedâ„¢ is a complete music theory learning series designed to make complex musical concepts easy to understand and apply. This eBook collection takes you step by step from the very basics of music to more advanced topics, using clear explanations and practical examples.\r\n\r\nWhether you are a beginner starting from zero or a self-taught musician looking to strengthen your foundation, TheoryUnlockedâ„¢ helps you understand how music really works. Topics such as notes, scales, chords, harmony, rhythm, and song structure are explained in a simple and logical way, without confusing technical language.\r\n\r\nPerfect for students, producers, and musicians who want to improve their musical knowledge, songwriting skills, and creative confidence.\r\n\r\nKey Features\r\n\r\nBeginner to advanced structured lessons\r\n\r\nSimple explanations with practical examples\r\n\r\nImproves composition, songwriting, and production\r\n\r\nEasy-to-read digital eBook format', '2026-02-28 12:45:31'),
(36, 18, 'SongBuilderâ„¢ | Songwriting Guide', '80.00', '0.00', 999998, 'digital', 'prod_69a2e3b9bd008.jpg', 'SongBuilderâ„¢ is a practical songwriting guide created to help beginners write better, more meaningful songs with confidence. This guide focuses on the core elements of songwriting â€” structure, melody, lyrics, and emotion â€” and explains them in a clear, step-by-step manner.\r\n\r\nYou will learn how to shape song ideas into complete songs, write engaging lyrics, build strong melodies, and understand common song structures used in modern music. SongBuilderâ„¢ is designed to be hands-on and easy to follow, making it perfect for singers, musicians, producers, and aspiring songwriters.\r\n\r\nNo advanced music theory knowledge is required. Just open the guide, follow the steps, and start writing songs that connect with listeners.\r\n\r\nKey Features\r\n\r\nBeginner-friendly songwriting techniques\r\n\r\nStep-by-step song structure guidance\r\n\r\nFocus on melody, lyrics, and emotion\r\n\r\nPractical exercises and creative tips', '2026-02-28 12:46:49'),
(37, 18, 'RhythmIQâ„¢ | Rhythm & Timing eBook', '80.00', '0.00', 999998, 'digital', 'prod_69a2e3da0abb0.jpg', 'RhythmIQâ„¢ is a focused rhythm and timing guide designed to help musicians and producers develop strong groove and musical feel across different genres. This eBook breaks down rhythm concepts in a simple and practical way, making it easy to understand and apply in real-world music situations.\r\n\r\nYou will learn about tempo, timing, rhythm patterns, subdivisions, and groove, along with exercises that improve consistency and confidence. RhythmIQâ„¢ is especially useful for producers, instrumentalists, drummers, and beat makers who want tighter timing and better musical control.\r\n\r\nWhether you play an instrument or produce music digitally, RhythmIQâ„¢ helps you lock into the beat and elevate your overall sound.\r\n\r\nKey Features\r\n\r\nClear explanation of rhythm and timing basics\r\n\r\nGenre-based rhythm examples\r\n\r\nPractical exercises for groove and consistency\r\n\r\nIdeal for producers and instrumentalists', '2026-02-28 12:47:22'),
(38, 17, 'NoteCraftâ„¢ | Sheet Music PDFs', '120.00', '0.00', 999999, 'digital', 'prod_69a2e4d83f5f2.jpg', 'NoteCraftâ„¢ is a professionally designed sheet music PDF collection created for clear reading, smooth practice, and confident performance. Each sheet is carefully formatted with clean notation, proper spacing, and an easy-to-follow layout, making it ideal for both beginners and experienced musicians.\r\n\r\nWhether you are practicing at home, teaching students, or performing live, NoteCraftâ„¢ helps you focus on the music without distraction. The print-friendly digital format ensures excellent readability on screens and paper alike.\r\n\r\nPerfect for students, teachers, and musicians who value clarity, accuracy, and professional presentation.\r\n\r\nKey Features\r\n\r\nClean and easy-to-read notation\r\n\r\nPractice and performance ready\r\n\r\nPrint-friendly digital PDFs\r\n\r\nSuitable for students and educators', '2026-02-28 12:51:36'),
(39, 17, 'ChordVerseâ„¢ | Lyrics & Chord Sheets', '120.00', '0.00', 999999, 'digital', 'prod_69a2e55f40378.jpg', 'ChordVerseâ„¢ is a carefully organized lyrics and chord sheet collection designed to make singing, playing, and songwriting easier and more enjoyable. Each song combines clear lyrics with accurate chord progressions, laid out in a clean and musician-friendly format.\r\n\r\nThis collection is perfect for guitarists, singers, and songwriters who want quick access to chords while practicing, performing, or writing new songs. With clear section labels and simple formatting, ChordVerseâ„¢ helps you stay focused on creativity rather than complexity.\r\n\r\nIdeal for jam sessions, rehearsals, and songwriting moments.\r\n\r\nKey Features\r\n\r\nAccurate chord progressions\r\n\r\nClean, readable lyric layout\r\n\r\nPerfect for singers and guitarists\r\n\r\nBeginner-friendly and practical', '2026-02-28 12:53:51'),
(40, 17, 'MelodyMapsâ„¢ | Lead Sheet Collection', '120.00', '0.00', 999999, 'digital', 'prod_69a2e639dc311.jpg', 'MelodyMapsâ„¢ is a simple and practical lead sheet collection designed for fast learning and live performance. Each lead sheet presents the essential melody lines and chord symbols in a clear, minimal format, allowing musicians to understand and play songs quickly.\r\n\r\nThis collection is ideal for rehearsals, jam sessions, and live gigs where time and clarity matter most. MelodyMapsâ„¢ removes unnecessary clutter and focuses only on what you need to perform with confidence.\r\n\r\nPerfect for musicians who want quick reference, flexibility, and performance-ready sheets.\r\n\r\nKey Features\r\n\r\nClear melody lines and chord symbols\r\n\r\nMinimal and easy-to-read layout\r\n\r\nGreat for quick learning and live use\r\n\r\nPrint and screen friendly PDFs', '2026-02-28 12:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 21, 5, 'good product', '2026-02-23 04:15:24'),
(2, 7, 21, 4, 'fast shipping', '2026-02-23 04:16:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','staff','admin','superadmin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `profile_image`) VALUES
(1, 'Dilmin Ekanayaka', 'dilmin@gmail.com', '$2y$10$nwoUL.nk6IJjKQhQC0O5wOhaFGpBNafmGYs2bLN8Eeze4m.YceMva', 'customer', '2026-02-22 11:20:35', 'u_1_699de26a09e47.jpg'),
(2, 'supun', 'supun@gmail.com', '$2y$10$er01yIu8WWn4Cfrwh7.OcecI1cvdA1JUEBsvKDHWc/qJ/CYG.5HTu', 'customer', '2026-02-22 11:21:17', NULL),
(3, 'Dilmin', 'dilmin99@gmail.com', '$2y$10$0PZYG7oYnKwe3eV8wydG3OAC9uZWwHfL4sL/NNk0YneIhSE9D6w2e', 'customer', '2026-02-22 11:36:29', NULL),
(4, 'Malsha', 'malsha@gmail.com', '$2y$10$Ee4KScWaFg3fGdNMtOjBOOdPfRCqLeVtsI.8FVUph1d7r2pCvbGxC', 'customer', '2026-02-22 11:41:19', 'u_4_699df3274c000.png'),
(5, 'Dilmin', 'dilmin2001@gmail.com', '$2y$10$jUd97VManThEhJLPAx0OiO.2baRyvLYB1hoPO88/s6K3h5Y9yQ51G', 'customer', '2026-02-22 12:26:19', NULL),
(6, 'mathsara', 'mathsara@gmail.com', '$2y$10$uv7UCdJ4zhcq7UbE6axZOeJWPdUOqbE3iUL2KS8DXMFfDP4QhRuiu', 'customer', '2026-02-22 17:50:55', NULL),
(7, 'inuka', 'inuka@gmail.com', '$2y$10$ieO0AwQjPelrT3m1w4ZpVejf/yPiYx/o2jaNYTbJyR7SgNDoYVI/G', 'customer', '2026-02-23 04:12:32', 'u_7_69a2e78d1c765.jpeg'),
(8, 'Super Admin', 'admin@melodymasters.com', '$2y$10$Qq8afGnDyA3Yly8rRv.b1enocC2Lzf3iPgpbvmm5XwBdJqUjd62Z2', 'superadmin', '2026-02-24 02:19:59', NULL),
(9, 'Denethmi', 'denethmi@gmail.com', '$2y$10$COdEL/8sE5vyS1Ur2Gcs6OOC7Q197bUZxDOfi94d/bpZLWLe87DIK', 'admin', '2026-02-24 05:46:52', NULL),
(10, 'Pramod', 'pramod@gmail.com', '$2y$10$NjeA0DFj2fRY0etTnX7NT.2zXoPkeOlVH0IbUWAE41qxXSw3DY2Mm', 'superadmin', '2026-02-24 05:47:45', NULL),
(11, 'tharushi', 'tharushi@gmail.com', '$2y$10$ycSdfn/8hYZXFdxiI8URSe9zMgmrCtFTMSG7Rb63f/9uNFISskYrS', 'staff', '2026-02-24 06:19:22', NULL),
(12, 'pasindu', 'pasindusupun461@gmail.com', '$2y$10$ysYMsRqRoMZYsxgnXzSvP.z4CUv6GZaMQ2lH2uR8FG1KWk3Vk2B7S', 'customer', '2026-02-24 14:10:40', NULL),
(13, 'inuka', 'inuka01@gmail.com', '$2y$10$ohYAyxddpuQSYCGcpIeJv.5PegMPqZ.WDn7lAeC8SP3Ao28xPuOse', 'staff', '2026-02-24 16:25:01', NULL),
(14, 'Nethmini', 'nethmini@gmail.com', '$2y$10$S6z8ZHZ0v2yiXqRg1sbWiexmap3zGYUsRNlUgRt68p.LiT0HbO8Oq', 'staff', '2026-02-28 13:01:50', NULL),
(15, 'testAdmin', 'testadmin@gmail.com', '$2y$10$W2PPMlLV0W8dcaTTswDq8.2zrlym1YC3cbOQszf48kNXwd57M4vTC', 'admin', '2026-03-03 07:48:10', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_parent_category` (`parent_id`);

--
-- Indexes for table `digital_products`
--
ALTER TABLE `digital_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_digital_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_order` (`order_id`),
  ADD KEY `fk_item_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_user` (`user_id`),
  ADD KEY `fk_review_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `digital_products`
--
ALTER TABLE `digital_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_parent_category` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `digital_products`
--
ALTER TABLE `digital_products`
  ADD CONSTRAINT `fk_digital_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
