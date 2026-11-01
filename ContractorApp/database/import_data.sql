-- ContractorApp SQLite → MySQL data export
-- Generated: 2026-10-31 11:23:37
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

-- Table: migrations (38 rows)
DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('1', '0001_01_01_000000_create_users_table', '1'),
('2', '0001_01_01_000001_create_cache_table', '1'),
('3', '0001_01_01_000002_create_jobs_table', '1'),
('4', '2026_09_27_143821_create_contractors_table', '1'),
('5', '2026_09_27_143822_create_roles_table', '1'),
('6', '2026_09_27_143823_create_permissions_table', '1'),
('7', '2026_09_27_143824_create_role_permissions_table', '1'),
('8', '2026_09_27_143825_add_contractor_and_role_to_users_table', '1'),
('9', '2026_09_27_143826_create_dealers_table', '1'),
('10', '2026_09_27_143827_create_clients_table', '1'),
('11', '2026_09_27_143828_create_projects_table', '1'),
('12', '2026_09_27_143829_create_project_locations_table', '1'),
('13', '2026_09_27_143830_create_works_table', '1'),
('14', '2026_09_27_143831_create_activity_logs_table', '1'),
('15', '2026_09_27_143832_create_settings_table', '1'),
('16', '2026_09_28_000001_add_username_to_users_table', '1'),
('17', '2026_09_29_000001_make_users_email_nullable', '1'),
('18', '2026_10_09_105809_add_next_meeting_to_projects_table', '1'),
('19', '2026_10_09_110256_create_project_meetings_table', '1'),
('20', '2026_10_09_112315_create_personal_access_tokens_table', '1'),
('21', '2026_10_10_060022_add_push_token_to_users_table', '1'),
('22', '2026_10_10_060023_create_push_notification_logs_table', '1'),
('23', '2026_10_10_120000_create_project_dealer_table', '1'),
('24', '2026_10_19_060000_make_project_id_nullable_in_push_notification_logs_table', '1'),
('25', '2026_10_19_000001_allow_summary_rows_in_push_notification_logs', '2'),
('26', '2026_10_20_000001_create_work_types_table', '2'),
('27', '2026_10_22_232300_make_contractor_contact_fields_nullable', '3'),
('28', '2026_10_30_214245_create_products_table', '4'),
('29', '2026_10_30_214246_create_purchases_table', '4'),
('30', '2026_10_30_214247_create_expenses_table', '4'),
('31', '2026_10_30_214248_create_project_payments_table', '4'),
('32', '2026_10_30_224104_make_purchase_qty_rate_nullable', '5'),
('33', '2026_10_30_234542_add_amount_to_works_table', '6'),
('34', '2026_10_30_234552_create_visits_table', '6'),
('35', '2026_10_31_000318_add_location_to_projects_table', '7'),
('36', '2026_10_31_004041_drop_expected_maturity_date_from_projects_table', '8'),
('37', '2026_10_31_100000_drop_dealers_tables', '9'),
('38', '2026_10_31_010620_add_location_to_visits_table', '10');

-- Table: roles (4 rows)
DELETE FROM `roles`;
INSERT INTO `roles` (`id`, `name`, `slug`, `type`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
('1', 'Super Admin', 'super-admin', 'admin', 'Full access to the entire application.', '1', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('2', 'Admin', 'admin', 'admin', 'Management user. Monitors contractors and their data; access is configurable.', '0', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('3', 'Contractor Owner', 'contractor-owner', 'contractor', 'Full access to the contractor\'s own business data and profile.', '1', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('4', 'Contractor Staff', 'contractor-staff', 'contractor', 'Team member who can view and update records but not delete them.', '0', '2026-10-19 12:23:02', '2026-10-19 12:23:02');

-- Table: permissions (61 rows)
DELETE FROM `permissions`;
INSERT INTO `permissions` (`id`, `name`, `group`, `description`, `created_at`, `updated_at`) VALUES
('1', 'dashboard.view', 'dashboard', 'View dashboard', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('2', 'contractors.view', 'contractors', 'View contractors', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('3', 'contractors.create', 'contractors', 'Create contractors', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('4', 'contractors.edit', 'contractors', 'Edit contractors', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('5', 'contractors.delete', 'contractors', 'Delete contractors', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('6', 'clients.view', 'clients', 'View clients', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('7', 'clients.create', 'clients', 'Create clients', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('8', 'clients.edit', 'clients', 'Edit clients', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('9', 'clients.delete', 'clients', 'Delete clients', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('10', 'projects.view', 'projects', 'View projects', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('11', 'projects.create', 'projects', 'Create projects', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('12', 'projects.edit', 'projects', 'Edit projects', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('13', 'projects.delete', 'projects', 'Delete projects', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('14', 'locations.view', 'locations', 'View project locations', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('15', 'locations.create', 'locations', 'Create project locations', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('16', 'locations.edit', 'locations', 'Edit project locations', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('17', 'locations.delete', 'locations', 'Delete project locations', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('18', 'works.view', 'works', 'View works', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('19', 'works.create', 'works', 'Create works', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('20', 'works.edit', 'works', 'Edit works', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('21', 'works.delete', 'works', 'Delete works', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('22', 'dealers.view', 'dealers', 'View contacts / dealers', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('23', 'dealers.create', 'dealers', 'Create contacts / dealers', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('24', 'dealers.edit', 'dealers', 'Edit contacts / dealers', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('25', 'dealers.delete', 'dealers', 'Delete contacts / dealers', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('26', 'profile.view', 'profile', 'View contractor profile', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('27', 'profile.edit', 'profile', 'Edit contractor profile', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('28', 'reports.view', 'reports', 'View reports', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('29', 'activity.view', 'activity', 'View activity', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('30', 'users.view', 'users', 'View users', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('31', 'users.create', 'users', 'Create users', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('32', 'users.edit', 'users', 'Edit users', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('33', 'users.delete', 'users', 'Delete users', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('34', 'roles.view', 'roles', 'View roles & permissions', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('35', 'roles.create', 'roles', 'Create roles & permissions', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('36', 'roles.edit', 'roles', 'Edit roles & permissions', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('37', 'roles.delete', 'roles', 'Delete roles & permissions', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('38', 'notifications.view', 'notifications', 'View notifications', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('39', 'notifications.send', 'notifications', 'Send notifications', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('40', 'settings.view', 'settings', 'View settings', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('41', 'settings.edit', 'settings', 'Edit settings', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('42', 'products.view', 'products', 'View products', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('43', 'products.create', 'products', 'Create products', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('44', 'products.edit', 'products', 'Edit products', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('45', 'products.delete', 'products', 'Delete products', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('46', 'purchases.view', 'purchases', 'View purchases', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('47', 'purchases.create', 'purchases', 'Create purchases', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('48', 'purchases.edit', 'purchases', 'Edit purchases', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('49', 'purchases.delete', 'purchases', 'Delete purchases', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('50', 'expenses.view', 'expenses', 'View expenses', '2026-10-30 21:51:48', '2026-10-30 21:51:48');
INSERT INTO `permissions` (`id`, `name`, `group`, `description`, `created_at`, `updated_at`) VALUES
('51', 'expenses.create', 'expenses', 'Create expenses', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('52', 'expenses.edit', 'expenses', 'Edit expenses', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('53', 'expenses.delete', 'expenses', 'Delete expenses', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('54', 'payments.view', 'payments', 'View payments received', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('55', 'payments.create', 'payments', 'Create payments received', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('56', 'payments.edit', 'payments', 'Edit payments received', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('57', 'payments.delete', 'payments', 'Delete payments received', '2026-10-30 21:51:48', '2026-10-30 21:51:48'),
('58', 'visits.view', 'visits', NULL, '2026-10-30 23:52:50', '2026-10-30 23:52:50'),
('59', 'visits.create', 'visits', NULL, '2026-10-30 23:52:50', '2026-10-30 23:52:50'),
('60', 'visits.edit', 'visits', NULL, '2026-10-30 23:52:50', '2026-10-30 23:52:50'),
('61', 'visits.delete', 'visits', NULL, '2026-10-30 23:52:50', '2026-10-30 23:52:50');

-- Table: role_permissions (139 rows)
DELETE FROM `role_permissions`;
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
('1', '29'),
('1', '7'),
('1', '9'),
('1', '8'),
('1', '6'),
('1', '3'),
('1', '5'),
('1', '4'),
('1', '2'),
('1', '1'),
('1', '23'),
('1', '25'),
('1', '24'),
('1', '22'),
('1', '15'),
('1', '17'),
('1', '16'),
('1', '14'),
('1', '39'),
('1', '38'),
('1', '11'),
('1', '13'),
('1', '12'),
('1', '10'),
('1', '28'),
('1', '35'),
('1', '37'),
('1', '36'),
('1', '34'),
('1', '41'),
('1', '40'),
('1', '31'),
('1', '33'),
('1', '32'),
('1', '30'),
('1', '19'),
('1', '21'),
('1', '20'),
('1', '18'),
('2', '29'),
('2', '7'),
('2', '9'),
('2', '8'),
('2', '6'),
('2', '3'),
('2', '5'),
('2', '4'),
('2', '2'),
('2', '1'),
('2', '23');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
('2', '25'),
('2', '24'),
('2', '22'),
('2', '15'),
('2', '17'),
('2', '16'),
('2', '14'),
('2', '39'),
('2', '38'),
('2', '11'),
('2', '13'),
('2', '12'),
('2', '10'),
('2', '28'),
('2', '35'),
('2', '37'),
('2', '36'),
('2', '34'),
('2', '41'),
('2', '40'),
('2', '31'),
('2', '33'),
('2', '32'),
('2', '30'),
('2', '19'),
('2', '21'),
('2', '20'),
('2', '18'),
('3', '29'),
('3', '7'),
('3', '9'),
('3', '8'),
('3', '6'),
('3', '1'),
('3', '23'),
('3', '25'),
('3', '24'),
('3', '22'),
('3', '15'),
('3', '17'),
('3', '16'),
('3', '14'),
('3', '27'),
('3', '26'),
('3', '11'),
('3', '13'),
('3', '12'),
('3', '10'),
('3', '28'),
('3', '19');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
('3', '21'),
('3', '20'),
('3', '18'),
('4', '29'),
('4', '7'),
('4', '8'),
('4', '6'),
('4', '1'),
('4', '22'),
('4', '15'),
('4', '16'),
('4', '14'),
('4', '26'),
('4', '11'),
('4', '12'),
('4', '10'),
('4', '19'),
('4', '20'),
('4', '18'),
('3', '51'),
('3', '53'),
('3', '52'),
('3', '50'),
('3', '55'),
('3', '57'),
('3', '56'),
('3', '54'),
('3', '43'),
('3', '45'),
('3', '44'),
('3', '42'),
('3', '47'),
('3', '49'),
('3', '48'),
('3', '46'),
('3', '58'),
('3', '59'),
('3', '60'),
('3', '61');

-- Table: contractors (5 rows)
DELETE FROM `contractors`;
INSERT INTO `contractors` (`id`, `company_name`, `contact_person`, `email`, `phone`, `alternate_phone`, `address`, `city`, `state`, `country`, `postal_code`, `website`, `logo_path`, `tax_number`, `license_number`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'BuildRight Contracting', 'John Mathew', 'office@buildright.test', '9847000001', NULL, '12 MG Road', 'Kochi', 'Kerala', 'India', '682016', 'https://buildright.example', NULL, '32ABCDE1234F1Z5', 'KL-CON-2019-0457', 'active', '2026-02-19 12:23:03', '2026-10-19 12:23:03', NULL),
('2', 'Coastal Constructions', 'Prakash Shetty', 'info@coastal.test', '9880000002', NULL, 'Plot 7, Baikampady Industrial Area', 'Mangaluru', 'Karnataka', 'India', '575011', NULL, NULL, '29FGHIJ5678K1Z2', 'KA-CON-2017-1123', 'active', '2026-02-19 12:23:03', '2026-10-19 12:23:03', NULL),
('3', 'Deccan Builders', 'Srinivas Reddy', 'contact@deccan.test', '9866000003', NULL, 'Financial District', 'Hyderabad', 'Telangana', 'India', '500032', NULL, NULL, NULL, NULL, 'inactive', '2026-02-19 12:23:03', '2026-10-19 12:23:03', NULL),
('5', 'abin', 'Abin', '', '9988767899', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, 'active', '2026-10-30 23:12:16', '2026-10-30 23:12:16', NULL),
('6', 'anil', 'Anil', '', '', NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, 'active', '2026-10-31 01:23:45', '2026-10-31 01:23:45', NULL);

-- Table: users (10 rows)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `contractor_id`, `role_id`, `phone`, `status`, `last_login_at`, `deleted_at`, `username`, `push_token`) VALUES
('1', 'Priya Menon', 'admin@contractorapp.test', '2026-10-19 12:23:02', '$2y$12$1h1mRCimJG7YVdH0KzLpNOZBfwAsqeRgvNSTguhaez4Ben7a2Wo4W', NULL, '2026-10-19 12:23:02', '2026-10-19 12:23:02', NULL, '1', NULL, 'active', NULL, NULL, 'priya.menon', NULL),
('2', 'Rahul Nair', 'manager@contractorapp.test', '2026-10-19 12:23:03', '$2y$12$qv/RwYTdDgBHr.cvZtvxTeeOMIKrBEyrBXQ6SQudo6t.seTM3TQJy', NULL, '2026-10-19 12:23:03', '2026-10-19 12:23:03', NULL, '2', NULL, 'active', NULL, NULL, 'rahul.nair', NULL),
('3', 'John Mathew', 'john@buildright.test', '2026-10-19 12:23:03', '$2y$12$45Akk4hES68LkY5N9khcl.X/w6ZncbA1p/.IkCzDqMNt08xuOGBUK', NULL, '2026-10-19 12:23:03', '2026-10-30 22:27:06', '1', '3', NULL, 'active', NULL, NULL, 'john.mathew', 'ExponentPushToken[test-device-123]'),
('4', 'Anu Joseph', 'anu@buildright.test', '2026-10-19 12:23:03', '$2y$12$r7SXZBNw6TwsIEeJYYznxu8asN7tPtgNc3mn7X0pIzl/W9SCLVPS6', NULL, '2026-10-19 12:23:03', '2026-10-19 12:23:03', '1', '4', NULL, 'active', NULL, NULL, 'anu.joseph', NULL),
('5', 'Prakash Shetty', 'prakash@coastal.test', '2026-10-19 12:23:03', '$2y$12$F.YMjRlU0erbalSHeooKCOx0tpuU4T8yee1KHBX9DQC584JEd/0WK', NULL, '2026-10-19 12:23:03', '2026-10-19 12:23:03', '2', '3', NULL, 'active', NULL, NULL, 'prakash.shetty', NULL),
('6', 'Srinivas Reddy', 'srinivas@deccan.test', '2026-10-19 12:23:03', '$2y$12$Gp.UNiWR6gbZYE7JRtIheOT20Of2vMyHwoYP91NHlAREtFmF/kivC', NULL, '2026-10-19 12:23:03', '2026-10-19 12:23:03', '3', '3', NULL, 'active', NULL, NULL, 'srinivas.reddy', NULL),
('7', 'Admin', 'admin@buildmate.test', NULL, '$2y$12$4cEQdgr2589ZNo0qJkSeYe89kox02/5cZfAIPnLQmdITR3m0VA5SG', NULL, '2026-10-30 22:01:54', '2026-10-31 01:25:58', NULL, '1', NULL, 'active', '2026-10-31 01:23:08', NULL, 'admin', NULL),
('8', 'Contractor Owner', 'owner@test.local', NULL, '$2y$12$s394rN.jMs5kZYeiFLG8COaW8BpRzIN4dpq9gTlpLbVgB1lF1JdBe', NULL, '2026-10-30 22:27:27', '2026-10-30 22:28:46', '1', '3', NULL, 'active', '2026-10-30 22:28:46', NULL, 'owner', NULL),
('9', 'Abin', NULL, NULL, '$2y$12$lEyZsrNmzAoLLMbVcSRVfOplQJYA0KsVnHf1f.QOCgLeMuyVXdzlG', NULL, '2026-10-30 23:12:16', '2026-10-31 16:10:34', '5', '3', '9988767899', 'active', '2026-10-31 16:10:34', NULL, 'abin', NULL),
('10', 'Anil', NULL, NULL, '$2y$12$KNj6lwfqGibcQfSHGL4NMO8ijMiasPSZvzAsOD8JW8Aki3Q6ix9KO', NULL, '2026-10-31 01:23:45', '2026-10-31 16:22:12', '6', '3', NULL, 'active', '2026-10-31 16:22:12', NULL, 'anil', NULL);

-- Table: settings (4 rows)
DELETE FROM `settings`;
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
('1', 'platform_name', 'ContractorApp', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('2', 'support_email', 'support@contractorapp.test', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('3', 'default_country', 'India', '2026-10-19 12:23:02', '2026-10-19 12:23:02'),
('4', 'records_per_page', '25', '2026-10-19 12:23:02', '2026-10-19 12:23:02');

-- Table: work_types (21 rows)
DELETE FROM `work_types`;
INSERT INTO `work_types` (`id`, `contractor_id`, `name`, `created_at`, `updated_at`) VALUES
('1', '1', 'Interior', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('2', '1', 'Exterior', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('3', '1', 'Outside', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('4', '1', 'Completed', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('5', '2', 'Interior', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('6', '2', 'Exterior', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('7', '2', 'Outside', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('8', '2', 'Completed', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('9', '3', 'Interior', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('10', '3', 'Exterior', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('11', '3', 'Outside', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('12', '3', 'Completed', '2026-10-21 15:03:04', '2026-10-21 15:03:04'),
('17', '5', 'Interior', '2026-10-30 23:12:16', '2026-10-30 23:12:16'),
('18', '5', 'Exterior', '2026-10-30 23:12:16', '2026-10-30 23:12:16'),
('19', '5', 'Outside', '2026-10-30 23:12:16', '2026-10-30 23:12:16'),
('20', '5', 'Completed', '2026-10-30 23:12:16', '2026-10-30 23:12:16'),
('21', '5', 'Flooring', '2026-10-31 00:26:37', '2026-10-31 00:26:37'),
('22', '6', 'Interior', '2026-10-31 01:23:45', '2026-10-31 01:23:45'),
('23', '6', 'Exterior', '2026-10-31 01:23:45', '2026-10-31 01:23:45'),
('24', '6', 'Outside', '2026-10-31 01:23:45', '2026-10-31 01:23:45'),
('25', '6', 'Completed', '2026-10-31 01:23:45', '2026-10-31 01:23:45');

-- Table: clients (11 rows)
DELETE FROM `clients`;
INSERT INTO `clients` (`id`, `contractor_id`, `name`, `company`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `country`, `postal_code`, `status`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '1', 'Thomas Varghese', 'Green Valley Developers', 'Thomas Varghese', 'thomas@greenvalley.test', '9847012345', NULL, 'Kakkanad', 'Kerala', 'India', NULL, 'active', NULL, '2026-03-13 12:23:03', '2026-10-19 12:23:03', NULL),
('2', '1', 'Abdul Rasheed', 'Harbour Homes', 'Abdul Rasheed', 'rasheed@harbourhomes.test', '9895123456', NULL, 'Kozhikode', 'Kerala', 'India', NULL, 'active', NULL, '2026-04-07 12:23:03', '2026-10-19 12:23:03', NULL),
('3', '1', 'St. Mary\'s School', 'St. Mary\'s Educational Trust', 'Fr. Joseph Mathew', 'office@stmarys.test', '9446098765', NULL, 'Pala', 'Kerala', 'India', NULL, 'active', NULL, '2026-05-02 12:23:03', '2026-10-19 12:23:03', NULL),
('4', '1', 'Meera Pillai', NULL, 'Meera Pillai', 'meera.pillai@example.test', '9744556677', NULL, 'Thrissur', 'Kerala', 'India', NULL, 'inactive', NULL, '2026-05-27 12:23:03', '2026-10-19 12:23:03', NULL),
('5', '2', 'Metro Logistics', 'Metro Logistics Pvt Ltd', 'Kiran Shetty', 'projects@metrologistics.test', '9880011223', NULL, 'Mangaluru', 'Karnataka', 'India', NULL, 'active', NULL, '2026-03-13 12:23:03', '2026-10-19 12:23:03', NULL),
('6', '2', 'Nandini Rao', 'Whitefield Tech Parks', 'Nandini Rao', 'nandini@wtp.test', '9900223344', NULL, 'Bengaluru', 'Karnataka', 'India', NULL, 'active', NULL, '2026-04-07 12:23:03', '2026-10-19 12:23:03', NULL),
('7', '2', 'Ramesh Gowda', NULL, 'Ramesh Gowda', 'ramesh.gowda@example.test', '9845566778', NULL, 'Mysuru', 'Karnataka', 'India', NULL, 'active', NULL, '2026-05-02 12:23:03', '2026-10-19 12:23:03', NULL),
('8', '3', 'Gachibowli Residency Association', NULL, 'Srinivas Reddy', 'gra@example.test', '9866554433', NULL, 'Hyderabad', 'Telangana', 'India', NULL, 'active', NULL, '2026-03-13 12:23:03', '2026-10-19 12:23:03', NULL),
('9', '5', 'Saifu', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'India', NULL, 'active', NULL, '2026-10-30 23:16:48', '2026-10-30 23:16:48', NULL),
('10', '5', 'Jijo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'India', NULL, 'active', NULL, '2026-10-31 00:26:27', '2026-10-31 00:26:27', NULL),
('11', '5', 'Joji', NULL, NULL, NULL, '1234567', NULL, NULL, NULL, 'India', NULL, 'active', NULL, '2026-10-31 00:42:51', '2026-10-31 00:42:51', NULL);

-- Table: projects (11 rows)
DELETE FROM `projects`;
INSERT INTO `projects` (`id`, `contractor_id`, `client_id`, `name`, `project_number`, `type_of_work`, `description`, `start_date`, `expected_end_date`, `actual_end_date`, `status`, `budget`, `brand_preferred`, `expected_qty`, `specification_details`, `notes`, `created_at`, `updated_at`, `deleted_at`, `next_meeting_at`, `next_meeting_notes`, `location`) VALUES
('1', '1', '1', 'Green Valley Villas', 'PRJ-0001', 'Residential – Villas', '12 villas with shared clubhouse. Foundation work done for 8.', '2026-05-22 00:00:00', '2026-12-18 00:00:00', NULL, 'active', '32000000', 'UltraTech', '4500', 'OPC 53 grade for slabs, PPC for plastering', NULL, '2026-05-15 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('2', '1', '2', 'Harbour View Apartments', 'PRJ-0002', 'Residential – Apartment', NULL, '2026-09-29 00:00:00', '2027-12-13 00:00:00', NULL, 'planning', '85000000', 'Ramco', '12000', 'G+9, RCC framed structure', 'Owner comparing quotes from two dealers.', '2026-09-22 12:23:03', '2026-10-19 12:24:00', NULL, '2026-10-19 09:30:00', 'Client walkthrough', NULL),
('3', '1', '3', 'St. Mary\'s School Extension', 'PRJ-0003', 'Institutional', NULL, '2026-04-02 00:00:00', '2026-10-09 00:00:00', '2026-10-11 00:00:00', 'completed', '9500000', 'ACC', '2200', 'New classroom block, 3 floors', NULL, '2026-03-26 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('4', '1', '3', 'St. Mary\'s Chapel Renovation', 'PRJ-0004', 'Renovation', NULL, '2026-09-09 00:00:00', '2026-10-24 00:00:00', NULL, 'on_hold', '2800000', NULL, '400', NULL, 'Waiting for trust committee approval.', '2026-09-02 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('5', '2', '5', 'Coastal Warehouse Complex', 'PRJ-0001', 'Industrial – Warehouse', NULL, '2026-07-21 00:00:00', '2027-01-02 00:00:00', NULL, 'active', '46000000', 'Dalmia', '8000', 'PEB structure with RCC flooring', NULL, '2026-07-14 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('6', '2', '6', 'Whitefield Tech Park – Block C', 'PRJ-0002', 'Commercial – Office', NULL, '2026-08-20 00:00:00', '2026-10-31 00:00:00', NULL, 'active', '150000000', 'UltraTech', '25000', 'Ready-mix concrete M30 for slabs', NULL, '2026-08-13 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('7', '2', '7', 'Individual House – Ramesh', 'PRJ-0003', 'Residential – House', 'Ground + 1 floor, 2,400 sq ft.', '2026-10-09 00:00:00', '2027-04-17 00:00:00', NULL, 'planning', '4200000', 'Birla A1', '650', NULL, NULL, '2026-10-02 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('8', '3', '8', 'Gachibowli Residency', 'PRJ-0001', 'Residential – Apartment', NULL, '2026-09-19 00:00:00', '2028-06-10 00:00:00', NULL, 'planning', '210000000', NULL, '30000', 'Two towers, G+14', NULL, '2026-09-12 12:23:03', '2026-10-19 12:23:03', NULL, NULL, NULL, NULL),
('9', '5', '9', 'Intra Restaurant Kalpetta', 'PRJ-0001', 'Interior', NULL, '2026-10-01 00:00:00', '2026-10-31 00:00:00', NULL, 'planning', '1000000', NULL, NULL, NULL, NULL, '2026-10-30 23:20:30', '2026-10-31 00:10:28', NULL, NULL, NULL, 'Kozhikode'),
('10', '5', '10', 'KLM Shopping Complex flooring', 'PRJ-0002', 'Flooring', NULL, '2026-10-31 00:00:00', '2026-11-07 00:00:00', NULL, 'planning', '800000', NULL, NULL, NULL, NULL, '2026-10-31 00:27:37', '2026-10-31 00:27:37', NULL, NULL, NULL, 'Kollam'),
('11', '5', '9', 'Hallway Renovation KLM', 'PRJ-0003', 'Interior', NULL, '2026-10-31 00:00:00', '2026-12-04 00:00:00', NULL, 'planning', '4000', NULL, NULL, NULL, NULL, '2026-10-31 00:48:08', '2026-10-31 00:48:08', NULL, NULL, NULL, 'Kollam');

-- Table: project_locations (8 rows)
DELETE FROM `project_locations`;
INSERT INTO `project_locations` (`id`, `contractor_id`, `project_id`, `name`, `address`, `city`, `district`, `state`, `country`, `postal_code`, `latitude`, `longitude`, `contact_person`, `contact_phone`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '1', '1', 'Villa plots', 'Survey No. 112, Kakkanad–Pallikkara Rd', 'Kakkanad', 'Ernakulam', 'Kerala', 'India', '682030', '10.0159', '76.3419', 'Thomas Varghese', '9847012345', NULL, '2026-05-15 13:23:03', '2026-10-19 12:23:03', NULL),
('2', '1', '2', 'Beach Road plot', 'Near Beach Rd, Kozhikode South', 'Kozhikode', 'Kozhikode', 'Kerala', 'India', '673001', NULL, NULL, 'Abdul Rasheed', '9895123456', NULL, '2026-09-22 13:23:03', '2026-10-19 12:23:03', NULL),
('3', '1', '3', 'School campus', 'Church Rd, Pala', 'Pala', 'Kottayam', 'Kerala', 'India', '686575', NULL, NULL, 'Fr. Joseph Mathew', '9446098765', NULL, '2026-03-26 13:23:03', '2026-10-19 12:23:03', NULL),
('4', '1', '4', 'Chapel', 'Church Rd, Pala', 'Pala', 'Kottayam', 'Kerala', 'India', '686575', NULL, NULL, 'Fr. Joseph Mathew', '9446098765', NULL, '2026-09-02 13:23:03', '2026-10-19 12:23:03', NULL),
('5', '2', '5', 'Warehouse site', 'Plot 7, Baikampady Industrial Area', 'Mangaluru', 'Dakshina Kannada', 'Karnataka', 'India', '575011', NULL, NULL, 'Kiran Shetty', '9880011223', NULL, '2026-07-14 13:23:03', '2026-10-19 12:23:03', NULL),
('6', '2', '6', 'Block C', 'ITPL Main Rd, Whitefield', 'Bengaluru', 'Bengaluru Urban', 'Karnataka', 'India', '560066', NULL, NULL, 'Nandini Rao', '9900223344', NULL, '2026-08-13 13:23:03', '2026-10-19 12:23:03', NULL),
('7', '2', '7', 'House plot', '4th Cross, Saraswathipuram', 'Mysuru', 'Mysuru', 'Karnataka', 'India', '570009', NULL, NULL, 'Ramesh Gowda', '9845566778', NULL, '2026-10-02 13:23:03', '2026-10-19 12:23:03', NULL),
('8', '3', '8', 'Tower site', 'Financial District, Nanakramguda', 'Hyderabad', 'Rangareddy', 'Telangana', 'India', '500032', NULL, NULL, 'Srinivas Reddy', '9866554433', NULL, '2026-09-12 13:23:03', '2026-10-19 12:23:03', NULL);

-- Table: products (4 rows)
DELETE FROM `products`;
INSERT INTO `products` (`id`, `contractor_id`, `name`, `unit`, `default_rate`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '1', 'Brick', NULL, NULL, NULL, '2026-10-30 22:50:53', '2026-10-30 22:50:53', NULL),
('2', '5', 'Brick', NULL, NULL, NULL, '2026-10-30 23:23:47', '2026-10-30 23:23:47', NULL),
('3', '5', 'Cement', NULL, NULL, NULL, '2026-10-30 23:23:53', '2026-10-30 23:23:53', NULL),
('4', '5', 'Sand', NULL, NULL, NULL, '2026-10-31 00:28:41', '2026-10-31 00:28:41', NULL);

-- Table: purchases (4 rows)
DELETE FROM `purchases`;
INSERT INTO `purchases` (`id`, `contractor_id`, `project_id`, `product_id`, `purchase_date`, `quantity`, `unit_amount`, `line_total`, `supplier`, `invoice_reference`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '1', '4', '1', '2026-10-30 00:00:00', '23', '22', '506', NULL, NULL, NULL, '2026-10-30 22:51:04', '2026-10-30 22:51:04', NULL),
('2', '5', '9', '3', '2026-10-30 00:00:00', '40', '500', '20000', NULL, NULL, NULL, '2026-10-30 23:24:08', '2026-10-30 23:24:08', NULL),
('3', '5', '10', '4', '2026-10-31 00:00:00', '70', '3000', '210000', NULL, NULL, NULL, '2026-10-31 00:28:53', '2026-10-31 00:28:53', NULL),
('4', '5', '11', '3', '2026-10-31 00:00:00', '5', '300', '1500', NULL, NULL, NULL, '2026-10-31 00:48:55', '2026-10-31 00:48:55', NULL);

-- Table: expenses (4 rows)
DELETE FROM `expenses`;
INSERT INTO `expenses` (`id`, `contractor_id`, `project_id`, `heading`, `category`, `expense_date`, `amount`, `payment_method`, `reference_number`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '1', '4', 'djhs', NULL, '2026-10-30 00:00:00', '5777', NULL, NULL, NULL, '2026-10-30 22:51:19', '2026-10-30 22:51:19', NULL),
('2', '5', '9', 'Transportation', NULL, '2026-10-30 00:00:00', '10000', NULL, NULL, NULL, '2026-10-30 23:26:25', '2026-10-30 23:26:25', NULL),
('3', '5', '10', 'transportation', 'material', '2026-10-31 00:00:00', '80000', 'Paytm', NULL, NULL, '2026-10-31 00:38:22', '2026-10-31 00:38:22', NULL),
('4', '5', '11', 'transportation', 'material', '2026-10-31 00:00:00', '500', 'Card', NULL, NULL, '2026-10-31 00:49:19', '2026-10-31 00:49:19', NULL);

-- Table: project_payments (5 rows)
DELETE FROM `project_payments`;
INSERT INTO `project_payments` (`id`, `contractor_id`, `project_id`, `payment_date`, `amount_received`, `payment_method`, `receipt_reference`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', '1', '4', '2026-10-30 00:00:00', '3000', NULL, NULL, NULL, '2026-10-30 22:51:34', '2026-10-30 22:51:34', NULL),
('2', '1', '2', '2026-10-30 00:00:00', '50000', NULL, NULL, NULL, '2026-10-30 23:01:09', '2026-10-30 23:01:09', NULL),
('3', '5', '9', '2026-10-30 00:00:00', '500000', NULL, NULL, NULL, '2026-10-30 23:26:47', '2026-10-30 23:26:47', NULL),
('4', '5', '10', '2026-10-31 00:00:00', '500000', 'Cash', NULL, NULL, '2026-10-31 00:38:44', '2026-10-31 00:38:44', NULL),
('5', '5', '11', '2026-10-31 00:00:00', '4000', 'Card', NULL, NULL, '2026-10-31 00:49:55', '2026-10-31 00:49:55', NULL);

-- Table: project_meetings (3 rows)
DELETE FROM `project_meetings`;
INSERT INTO `project_meetings` (`id`, `project_id`, `contractor_id`, `created_by`, `meeting_at`, `purpose`, `created_at`, `updated_at`) VALUES
('1', '1', '1', '3', '2026-10-19 15:00:00', 'Site Inspection', '2026-10-19 12:23:47', '2026-10-19 12:23:47'),
('2', '1', '1', '3', '2026-10-19 15:00:00', 'Site Inspection', '2026-10-19 12:30:55', '2026-10-19 12:30:55'),
('3', '9', '5', '9', '2026-10-23 23:28:00', 'Site visit', '2026-10-30 23:27:56', '2026-10-30 23:27:56');

-- Table: visits (4 rows)
DELETE FROM `visits`;
INSERT INTO `visits` (`id`, `contractor_id`, `client_id`, `description`, `purpose`, `meeting_date`, `next_meeting_date`, `created_at`, `updated_at`, `deleted_at`, `location`) VALUES
('1', '5', '9', 'note', 'new project', '2026-10-30 00:00:00', '2026-10-30 00:00:00', '2026-10-31 00:00:20', '2026-10-31 00:00:20', NULL, NULL),
('2', '5', '11', NULL, 'site visit', '2026-10-31 00:00:00', '2026-11-06 00:00:00', '2026-10-31 00:43:04', '2026-10-31 01:14:10', NULL, 'Kollam'),
('3', '5', '9', NULL, 'To', '2026-10-22 00:00:00', '2026-10-31 00:00:00', '2026-10-31 01:02:50', '2026-10-31 01:02:50', NULL, NULL),
('4', '5', '9', NULL, NULL, '2026-10-31 00:00:00', '2026-11-07 00:00:00', '2026-10-31 01:17:00', '2026-10-31 01:17:00', NULL, 'Kollam');

-- Table: works (12 rows)
DELETE FROM `works`;
INSERT INTO `works` (`id`, `contractor_id`, `project_id`, `client_id`, `assigned_to`, `title`, `description`, `priority`, `status`, `start_date`, `due_date`, `completed_at`, `created_at`, `updated_at`, `deleted_at`, `amount`) VALUES
('1', '1', '1', '1', NULL, 'Foundation pour – Villas 9-12', NULL, 'high', 'in_progress', '2026-10-16 00:00:00', '2026-10-21 00:00:00', NULL, '2026-10-07 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('2', '1', '1', '1', NULL, 'Site inspection with engineer', NULL, 'medium', 'pending', '2026-10-16 00:00:00', '2026-10-20 00:00:00', NULL, '2026-10-06 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('3', '1', '1', '1', NULL, 'Plastering – Villas 1-4', NULL, 'medium', 'completed', '2026-10-16 00:00:00', '2026-10-13 00:00:00', '2026-10-13 16:30:00', '2026-09-29 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('4', '1', '2', '2', NULL, 'Soil testing', NULL, 'high', 'pending', '2026-10-16 00:00:00', '2026-10-24 00:00:00', NULL, '2026-10-10 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('5', '1', '2', '2', NULL, 'Submit building permit', NULL, 'urgent', 'pending', '2026-10-16 00:00:00', '2026-10-22 00:00:00', NULL, '2026-10-08 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('6', '1', '3', '3', NULL, 'Final handover inspection', NULL, 'high', 'completed', '2026-10-16 00:00:00', '2026-10-10 00:00:00', '2026-10-10 16:30:00', '2026-09-26 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('7', '2', '5', '5', NULL, 'Roof sheeting', NULL, 'high', 'in_progress', '2026-10-16 00:00:00', '2026-10-29 00:00:00', NULL, '2026-10-15 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('8', '2', '5', '5', NULL, 'Floor hardener application', NULL, 'medium', 'pending', '2026-10-16 00:00:00', '2026-11-08 00:00:00', NULL, '2026-10-18 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('9', '2', '6', '6', NULL, 'Slab casting – 6th floor', NULL, 'urgent', 'pending', '2026-10-16 00:00:00', '2026-10-21 00:00:00', NULL, '2026-10-07 12:23:03', '2026-10-19 12:23:03', NULL, NULL),
('10', '5', '9', '9', NULL, 'Flooring', NULL, 'medium', 'completed', '2026-10-30 00:00:00', '2026-10-31 00:00:00', '2026-10-30 23:23:10', '2026-10-30 23:22:27', '2026-10-30 23:23:10', NULL, NULL),
('11', '5', '10', '10', NULL, 'Flooring', NULL, 'medium', 'completed', '2026-10-31 00:00:00', '2026-11-07 00:00:00', '2026-10-31 00:41:53', '2026-10-31 00:28:18', '2026-10-31 00:41:53', NULL, '30000'),
('12', '5', '11', '9', NULL, 'Cleaning', NULL, 'medium', 'pending', '2026-10-31 00:00:00', '2026-11-07 00:00:00', NULL, '2026-10-31 00:48:34', '2026-10-31 00:48:34', NULL, '3000');

SET FOREIGN_KEY_CHECKS=1;