-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: 30 يوليو 2025 الساعة 13:36
-- إصدار الخادم: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u414795990_qiyas`
--

-- --------------------------------------------------------

--
-- بنية الجدول `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `program_order`) VALUES
(1, 'عملية الشبكية', 3),
(3, 'النظارات الطبية', 5),
(4, 'عملية قرنية', 4),
(5, 'عملية مياه زرقاء', 2),
(6, 'عملية المياه البيضاء', 1),
(7, 'حملات التوعية الطبية', 6);

-- --------------------------------------------------------

--
-- بنية الجدول `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('text','single_choice','multiple_choice','rating','dropdown') NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `question_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `questions`
--

INSERT INTO `questions` (`id`, `question_text`, `question_type`, `is_required`, `question_order`) VALUES
(1, 'اسم المستفيد', 'text', 0, 1),
(2, 'رقم الجوال', 'text', 0, 2),
(3, 'الجنس', 'single_choice', 1, 3),
(4, 'البرنامج المستفاد منه', 'single_choice', 1, 4),
(5, 'مستوى الرضا العام عن الخدمات المقدمة لك', 'rating', 1, 5),
(6, 'مدى رضاك على تجاوب وتفاعل الموظفين مع متطلباتك كمستفيد من خدمات الجمعية', 'rating', 1, 6),
(7, 'مدى رضاك عن تقديم الجمعية للخدمة في وقت الحاجة إليها من غير تأخير', 'rating', 1, 7),
(8, 'مدى رضاك على نظام تقديم الشكاوي و الاقتراحات في الجمعية', 'rating', 1, 8),
(9, 'مدى رضاك عن طرق التواصل مع الجمعية', 'rating', 1, 9),
(10, 'هل أنت راضي عن تشخيص الباحث الاجتماعي لحالة المستفيد؟', 'rating', 1, 10),
(11, 'رضاك عن بوابة الخدمات الالكترونية للمستفيدين.', 'rating', 1, 11),
(12, 'كيف عرفت أو سمعت عن جمعية عيون طيبة الخيرية؟', 'single_choice', 0, 12),
(13, 'اقتراحات وملاحظات لتحسين الخدمة', 'text', 0, 13);

-- --------------------------------------------------------

--
-- بنية الجدول `question_options`
--

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `question_options`
--

INSERT INTO `question_options` (`id`, `question_id`, `option_text`) VALUES
(1, 3, 'ذكر'),
(2, 3, 'أنثى'),
(3, 12, 'عن طريق إعلان في مواقع التواصل'),
(4, 12, 'عن طريق الأصدقاء والأهل'),
(5, 12, 'عن طريق البحث على قوقل'),
(6, 12, 'عن طريق جمعية أخرى');

-- --------------------------------------------------------

--
-- بنية الجدول `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `system_name` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `primary_font_url` varchar(255) DEFAULT NULL,
  `primary_font_name` varchar(100) DEFAULT NULL,
  `primary_color` varchar(7) DEFAULT '#1a535c',
  `secondary_color` varchar(7) DEFAULT '#f7b538'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `settings`
--

INSERT INTO `settings` (`id`, `site_name`, `system_name`, `logo_path`, `primary_font_url`, `primary_font_name`, `primary_color`, `secondary_color`) VALUES
(1, 'جمعية عيون طيبة', 'قياس رضا المستفيدين', 'uploads/6872eac4a6405.png', 'https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap', 'Almarai', '#083e3f', '#b6862b');

-- --------------------------------------------------------

--
-- بنية الجدول `survey_answers`
--

CREATE TABLE `survey_answers` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `survey_answers`
--

INSERT INTO `survey_answers` (`id`, `response_id`, `question_id`, `answer_text`, `rating`) VALUES
(474, 52, 5, NULL, 5),
(475, 52, 6, NULL, 5),
(476, 52, 7, NULL, 5),
(477, 52, 8, NULL, 5),
(478, 52, 9, NULL, 5),
(479, 52, 10, NULL, 5),
(480, 52, 11, NULL, 5),
(481, 52, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(482, 52, 13, '', NULL),
(483, 53, 5, NULL, 5),
(484, 53, 6, NULL, 5),
(485, 53, 7, NULL, 5),
(486, 53, 8, NULL, 5),
(487, 53, 9, NULL, 5),
(488, 53, 10, NULL, 5),
(489, 53, 11, NULL, 5),
(490, 53, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(491, 53, 13, 'الايوجد', NULL),
(492, 54, 5, NULL, 5),
(493, 54, 6, NULL, 5),
(494, 54, 7, NULL, 5),
(495, 54, 8, NULL, 5),
(496, 54, 9, NULL, 5),
(497, 54, 10, NULL, 5),
(498, 54, 11, NULL, 5),
(499, 54, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(500, 54, 13, 'العمل على زيارة القرى ومساعدة سكانها لمعرفة الحالة الصحية وعمل الفحوصات اللازمة للعيون ', NULL),
(501, 55, 5, NULL, 5),
(502, 55, 6, NULL, 5),
(503, 55, 7, NULL, 5),
(504, 55, 8, NULL, 5),
(505, 55, 9, NULL, 5),
(506, 55, 10, NULL, 5),
(507, 55, 11, NULL, 5),
(508, 55, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(509, 55, 13, 'خدمة ممتازة', NULL),
(510, 56, 5, NULL, 5),
(511, 56, 6, NULL, 5),
(512, 56, 7, NULL, 5),
(513, 56, 8, NULL, 5),
(514, 56, 9, NULL, 5),
(515, 56, 10, NULL, 5),
(516, 56, 11, NULL, 5),
(517, 56, 12, 'عن طريق الأصدقاء والأهل', NULL),
(518, 56, 13, '', NULL),
(519, 57, 5, NULL, 5),
(520, 57, 6, NULL, 5),
(521, 57, 7, NULL, 5),
(522, 57, 8, NULL, 5),
(523, 57, 9, NULL, 5),
(524, 57, 10, NULL, 5),
(525, 57, 11, NULL, 5),
(526, 57, 12, 'عن طريق الأصدقاء والأهل', NULL),
(527, 57, 13, 'جزاكم الله خيرا و بارك فيكم أجمعين يا ليت يكون فيه جمعية لعلاج الأسنان', NULL),
(537, 59, 5, NULL, 5),
(538, 59, 6, NULL, 5),
(539, 59, 7, NULL, 5),
(540, 59, 8, NULL, 5),
(541, 59, 9, NULL, 5),
(542, 59, 10, NULL, 5),
(543, 59, 11, NULL, 5),
(544, 59, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(545, 59, 13, '', NULL),
(546, 60, 5, NULL, 5),
(547, 60, 6, NULL, 5),
(548, 60, 7, NULL, 5),
(549, 60, 8, NULL, 5),
(550, 60, 9, NULL, 5),
(551, 60, 10, NULL, 5),
(552, 60, 11, NULL, 5),
(553, 60, 12, 'عن طريق الأصدقاء والأهل', NULL),
(554, 60, 13, 'شكرا لكم واسأل الله ان يجعلها في ميزان حسناتكم', NULL),
(555, 61, 5, NULL, 5),
(556, 61, 6, NULL, 5),
(557, 61, 7, NULL, 5),
(558, 61, 8, NULL, 5),
(559, 61, 9, NULL, 5),
(560, 61, 10, NULL, 5),
(561, 61, 11, NULL, 5),
(562, 61, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(563, 61, 13, '', NULL),
(564, 62, 5, NULL, 5),
(565, 62, 6, NULL, 5),
(566, 62, 7, NULL, 5),
(567, 62, 8, NULL, 5),
(568, 62, 9, NULL, 5),
(569, 62, 10, NULL, 5),
(570, 62, 11, NULL, 5),
(571, 62, 12, 'عن طريق الأصدقاء والأهل', NULL),
(572, 62, 13, 'شكرا جزيلا', NULL),
(573, 63, 5, NULL, 5),
(574, 63, 6, NULL, 5),
(575, 63, 7, NULL, 5),
(576, 63, 8, NULL, 5),
(577, 63, 9, NULL, 5),
(578, 63, 10, NULL, 5),
(579, 63, 11, NULL, 5),
(580, 63, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(581, 63, 13, '', NULL),
(582, 64, 5, NULL, 5),
(583, 64, 6, NULL, 5),
(584, 64, 7, NULL, 5),
(585, 64, 8, NULL, 5),
(586, 64, 9, NULL, 5),
(587, 64, 10, NULL, 5),
(588, 64, 11, NULL, 5),
(589, 64, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(590, 64, 13, 'نشكر كل جهودكم صغيرها وكبيرها وكل القائمين على فعل الخير اثابكم الله', NULL),
(591, 65, 5, NULL, 5),
(592, 65, 6, NULL, 5),
(593, 65, 7, NULL, 5),
(594, 65, 8, NULL, 5),
(595, 65, 9, NULL, 5),
(596, 65, 10, NULL, 5),
(597, 65, 11, NULL, 5),
(598, 65, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(599, 65, 13, '', NULL),
(600, 66, 5, NULL, 5),
(601, 66, 6, NULL, 5),
(602, 66, 7, NULL, 5),
(603, 66, 8, NULL, 5),
(604, 66, 9, NULL, 5),
(605, 66, 10, NULL, 5),
(606, 66, 11, NULL, 5),
(607, 66, 12, 'عن طريق إعلان في مواقع التواصل', NULL),
(608, 66, 13, '', NULL),
(609, 67, 5, NULL, 5),
(610, 67, 6, NULL, 5),
(611, 67, 7, NULL, 5),
(612, 67, 8, NULL, 5),
(613, 67, 9, NULL, 5),
(614, 67, 10, NULL, 5),
(615, 67, 11, NULL, 5),
(616, 67, 12, 'عن طريق الأصدقاء والأهل', NULL),
(617, 67, 13, 'الله يوفقكم ', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `survey_multiple_choice_answers`
--

CREATE TABLE `survey_multiple_choice_answers` (
  `id` int(11) NOT NULL,
  `survey_answer_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `survey_responses`
--

CREATE TABLE `survey_responses` (
  `id` int(11) NOT NULL,
  `beneficiary_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `submission_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `survey_responses`
--

INSERT INTO `survey_responses` (`id`, `beneficiary_name`, `phone_number`, `gender`, `program_id`, `suggestions`, `submission_date`) VALUES
(52, 'صبا احمد راتب القج', '0552701803', 'female', 1, '', '2025-07-22 11:20:00'),
(53, 'عليان عيد الرشيدي', '0556337504', 'male', 4, '', '2025-07-22 11:30:38'),
(54, 'ماجد الحربي', '0533303988', 'male', 6, '', '2025-07-22 11:37:40'),
(55, 'سلمى سليم الصاعدي', '0543303133', 'female', 6, '', '2025-07-22 11:38:57'),
(56, 'خديجة محمد طاهر', '٠٥٠٠٧١٩٠٣٢', 'female', 6, '', '2025-07-22 11:50:15'),
(57, 'حليمة غلام', '0590094318', 'female', 6, '', '2025-07-22 12:09:09'),
(59, 'حمد عالي العلوي', '', 'male', 6, '', '2025-07-22 15:21:23'),
(60, 'عائشه علي', '0560022192', 'female', 1, '', '2025-07-22 15:45:07'),
(61, 'عيده مفلح الح', '0502254509', 'female', 6, '', '2025-07-22 18:20:05'),
(62, 'غزيل عوض معوض الجابري', '0508544525', 'female', 6, '', '2025-07-22 19:52:07'),
(63, 'حامده حامد الصبحي', '0592459220', 'female', 6, '', '2025-07-22 20:09:22'),
(64, '', '', 'female', 6, '', '2025-07-23 02:48:38'),
(65, 'عبدالوهاب عبدالخالق', '', 'male', 6, '', '2025-07-23 10:34:55'),
(66, 'حمد عالي العلوي', '', 'male', 6, '', '2025-07-23 11:33:38'),
(67, 'حصة ناصر سهل', '0559699047', 'female', 6, '', '2025-07-25 00:44:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_name` (`program_name`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `survey_multiple_choice_answers`
--
ALTER TABLE `survey_multiple_choice_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_answer_id` (`survey_answer_id`),
  ADD KEY `option_id` (`option_id`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `survey_answers`
--
ALTER TABLE `survey_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=618;

--
-- AUTO_INCREMENT for table `survey_multiple_choice_answers`
--
ALTER TABLE `survey_multiple_choice_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD CONSTRAINT `survey_answers_ibfk_1` FOREIGN KEY (`response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `survey_multiple_choice_answers`
--
ALTER TABLE `survey_multiple_choice_answers`
  ADD CONSTRAINT `survey_multiple_choice_answers_ibfk_1` FOREIGN KEY (`survey_answer_id`) REFERENCES `survey_answers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `survey_multiple_choice_answers_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `question_options` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `survey_responses_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
