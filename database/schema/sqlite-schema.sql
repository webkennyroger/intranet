CREATE TABLE "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "username" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "status" varchar not null default 'active',
  "department" varchar,
  "remember_token" varchar,
  "deleted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "birth_date" date,
  "last_login_at" datetime
);
CREATE UNIQUE INDEX "users_username_unique" on "users"("username");
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" varchar not null,
  "queue" varchar not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs"(
  "connection",
  "queue",
  "failed_at"
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "description" varchar
);
CREATE UNIQUE INDEX "roles_name_guard_name_unique" on "roles"(
  "name",
  "guard_name"
);
CREATE TABLE "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE "service_links"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "icon" varchar not null,
  "url" varchar not null,
  "order" integer not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "service_links_is_active_order_index" on "service_links"(
  "is_active",
  "order"
);
CREATE TABLE "site_settings"(
  "id" integer primary key autoincrement not null,
  "primary_color" varchar not null default '#0C4A34',
  "created_at" datetime,
  "updated_at" datetime,
  "nav_header_color" varchar not null default '#0C4A34',
  "header_color" varchar not null default '#0C4A34',
  "sidebar_color" varchar not null default '#0C4A34',
  "text_color" varchar not null default '#0F172A'
);
CREATE TABLE "photo_albums"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" text,
  "cover_image_path" varchar,
  "event_date" date,
  "is_published" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "views_count" integer not null default '0'
);
CREATE TABLE "photos"(
  "id" integer primary key autoincrement not null,
  "photo_album_id" integer not null,
  "image_path" varchar not null,
  "caption" varchar,
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("photo_album_id") references "photo_albums"("id") on delete cascade
);
CREATE TABLE "videos"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" text,
  "url" varchar not null,
  "thumbnail_path" varchar,
  "is_published" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "views_count" integer not null default '0'
);
CREATE TABLE "forum_topics"(
  "id" integer primary key autoincrement not null,
  "author_id" integer not null,
  "title" varchar not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("author_id") references "users"("id") on delete cascade
);
CREATE TABLE "forum_replies"(
  "id" integer primary key autoincrement not null,
  "forum_topic_id" integer not null,
  "author_id" integer not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("forum_topic_id") references "forum_topics"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete cascade
);
CREATE TABLE "activity_logs"(
  "id" integer primary key autoincrement not null,
  "action" varchar not null,
  "description" varchar not null,
  "subject_type" varchar,
  "subject_id" integer,
  "user_id" integer,
  "changes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "activity_logs_subject_type_subject_id_index" on "activity_logs"(
  "subject_type",
  "subject_id"
);
CREATE INDEX "activity_logs_action_index" on "activity_logs"("action");
CREATE TABLE "media"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "path" varchar not null,
  "user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "source" varchar,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE "article_category"(
  "article_id" integer not null,
  "category_id" integer not null,
  foreign key("article_id") references "articles"("id") on delete cascade,
  foreign key("category_id") references "categories"("id") on delete cascade,
  primary key("article_id", "category_id")
);
CREATE TABLE "articles"(
  "id" integer primary key autoincrement not null,
  "author_id" integer not null,
  "title" varchar not null,
  "excerpt" text not null,
  "body" text not null,
  "cover_color" varchar not null,
  "cover_icon" varchar not null,
  "cover_label" varchar,
  "is_published" tinyint(1) not null default('0'),
  "published_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "featured_image" varchar,
  "custom_fields" text,
  "views_count" integer not null default '0',
  foreign key("author_id") references users("id") on delete no action on update no action
);
CREATE INDEX "articles_is_published_published_at_index" on "articles"(
  "is_published",
  "published_at"
);
CREATE TABLE "birthday_banners"(
  "id" integer primary key autoincrement not null,
  "month" integer not null,
  "image_path" varchar not null,
  "is_published" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "birthday_banners_month_unique" on "birthday_banners"(
  "month"
);
CREATE TABLE "comments"(
  "id" integer primary key autoincrement not null,
  "article_id" integer not null,
  "author_id" integer not null,
  "parent_id" integer,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("article_id") references "articles"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete cascade,
  foreign key("parent_id") references "comments"("id") on delete cascade
);
CREATE TABLE "council_processes"(
  "id" integer primary key autoincrement not null,
  "sei_number" varchar not null,
  "subject" varchar not null,
  "assigned_to" varchar not null,
  "interested_party" varchar not null,
  "external_url" varchar,
  "is_published" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE "likes"(
  "id" integer primary key autoincrement not null,
  "likeable_type" varchar not null,
  "likeable_id" integer not null,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "likes_likeable_type_likeable_id_index" on "likes"(
  "likeable_type",
  "likeable_id"
);
CREATE UNIQUE INDEX "likes_likeable_type_likeable_id_user_id_unique" on "likes"(
  "likeable_type",
  "likeable_id",
  "user_id"
);
CREATE TABLE "categories"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "description" varchar,
  "color" varchar not null default('#7C3AED'),
  "type" varchar not null default 'article',
  "icon" varchar,
  "order" integer
);
CREATE TABLE "documents"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" text,
  "file_path" varchar,
  "is_published" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "document_category_id" integer,
  "link" varchar,
  foreign key("document_category_id") references "categories"("id") on delete set null
);
CREATE TABLE "trainings"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" text,
  "workload_hours" integer,
  "starts_at" date,
  "is_published" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "training_category_id" integer,
  "links" text,
  foreign key("training_category_id") references "categories"("id") on delete set null
);
CREATE TABLE "events"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "description" text,
  "location" varchar,
  "starts_at" datetime not null,
  "ends_at" datetime,
  "is_published" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "cover_image" varchar,
  "link" varchar,
  "file_path" varchar,
  "order" integer not null default('0'),
  "color" varchar,
  "event_category_id" integer,
  foreign key("event_category_id") references "categories"("id") on delete set null
);
CREATE UNIQUE INDEX "categories_type_slug_unique" on "categories"(
  "type",
  "slug"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2026_10_01_191025_create_permission_tables',1);
INSERT INTO migrations VALUES(5,'2026_10_02_000001_create_service_links_table',1);
INSERT INTO migrations VALUES(6,'2026_10_02_000002_create_categories_table',1);
INSERT INTO migrations VALUES(7,'2026_10_02_000003_create_articles_table',1);
INSERT INTO migrations VALUES(8,'2026_10_02_000004_create_article_likes_table',1);
INSERT INTO migrations VALUES(9,'2026_10_02_182047_create_site_settings_table',1);
INSERT INTO migrations VALUES(10,'2026_10_02_191643_add_chrome_colors_to_site_settings_table',1);
INSERT INTO migrations VALUES(11,'2026_10_05_162656_create_documents_table',1);
INSERT INTO migrations VALUES(12,'2026_10_05_163157_create_council_members_table',1);
INSERT INTO migrations VALUES(13,'2026_10_05_163547_create_photo_albums_table',1);
INSERT INTO migrations VALUES(14,'2026_10_05_163548_create_photos_table',1);
INSERT INTO migrations VALUES(15,'2026_10_05_164031_create_videos_table',1);
INSERT INTO migrations VALUES(16,'2026_10_05_164348_create_events_table',1);
INSERT INTO migrations VALUES(17,'2026_10_05_164710_create_trainings_table',1);
INSERT INTO migrations VALUES(18,'2026_10_05_171418_add_birth_date_to_users_table',1);
INSERT INTO migrations VALUES(19,'2026_10_05_171926_create_forum_topics_table',1);
INSERT INTO migrations VALUES(20,'2026_10_05_171927_create_forum_replies_table',1);
INSERT INTO migrations VALUES(21,'2026_10_05_195144_add_last_login_at_to_users_table',1);
INSERT INTO migrations VALUES(22,'2026_10_05_212423_create_activity_logs_table',1);
INSERT INTO migrations VALUES(23,'2026_10_06_154701_add_featured_image_and_allow_comments_to_articles_table',1);
INSERT INTO migrations VALUES(24,'2026_10_06_160505_add_custom_fields_to_articles_table',1);
INSERT INTO migrations VALUES(25,'2026_10_06_181157_add_description_to_categories_table',1);
INSERT INTO migrations VALUES(26,'2026_10_06_184441_create_media_table',1);
INSERT INTO migrations VALUES(27,'2026_10_06_191352_add_source_to_media_table',1);
INSERT INTO migrations VALUES(28,'2026_10_07_144434_add_description_to_roles_table',1);
INSERT INTO migrations VALUES(29,'2026_10_07_173014_add_color_to_categories_table',1);
INSERT INTO migrations VALUES(30,'2026_10_07_204411_create_article_category_table',1);
INSERT INTO migrations VALUES(31,'2026_10_07_204412_migrate_articles_category_id_to_pivot',1);
INSERT INTO migrations VALUES(32,'2026_10_08_211218_remove_allow_comments_from_articles_table',1);
INSERT INTO migrations VALUES(33,'2026_10_09_134045_create_photo_album_likes_table',1);
INSERT INTO migrations VALUES(34,'2026_10_09_142740_create_video_likes_table',1);
INSERT INTO migrations VALUES(35,'2026_10_13_092126_create_document_categories_table',1);
INSERT INTO migrations VALUES(36,'2026_10_13_092127_add_document_category_id_to_documents_table',1);
INSERT INTO migrations VALUES(37,'2026_10_13_105512_make_file_path_nullable_on_documents_table',1);
INSERT INTO migrations VALUES(38,'2026_10_13_110430_add_link_to_documents_table',1);
INSERT INTO migrations VALUES(39,'2026_10_13_113739_add_color_to_document_categories_table',1);
INSERT INTO migrations VALUES(40,'2026_10_14_102549_add_cover_image_to_events_table',1);
INSERT INTO migrations VALUES(41,'2026_10_14_135041_create_birthday_banners_table',1);
INSERT INTO migrations VALUES(42,'2026_10_14_163950_add_views_count_to_articles_table',1);
INSERT INTO migrations VALUES(43,'2026_10_14_163951_add_views_count_to_photo_albums_table',1);
INSERT INTO migrations VALUES(44,'2026_10_14_163952_add_views_count_to_videos_table',1);
INSERT INTO migrations VALUES(45,'2026_10_15_141755_create_training_categories_table',1);
INSERT INTO migrations VALUES(46,'2026_10_15_141756_add_training_category_id_to_trainings_table',1);
INSERT INTO migrations VALUES(47,'2026_10_15_145101_replace_external_url_with_links_on_trainings_table',1);
INSERT INTO migrations VALUES(48,'2026_10_15_174134_add_link_and_file_path_to_events_table',1);
INSERT INTO migrations VALUES(49,'2026_10_16_092008_add_order_to_events_table',1);
INSERT INTO migrations VALUES(50,'2026_10_16_095949_create_comments_table',1);
INSERT INTO migrations VALUES(51,'2026_10_16_095950_add_tags_to_articles_table',1);
INSERT INTO migrations VALUES(52,'2026_10_16_131119_drop_tags_from_articles_table',1);
INSERT INTO migrations VALUES(53,'2026_10_16_140342_create_council_processes_table',1);
INSERT INTO migrations VALUES(54,'2026_10_19_144356_add_text_color_to_site_settings_table',1);
INSERT INTO migrations VALUES(55,'2026_10_20_105838_add_color_to_events_table',1);
INSERT INTO migrations VALUES(56,'2026_10_20_113454_create_event_categories_table',1);
INSERT INTO migrations VALUES(57,'2026_10_20_114927_add_event_category_id_to_events_table',1);
INSERT INTO migrations VALUES(58,'2026_10_20_115453_make_color_nullable_on_events_table',1);
INSERT INTO migrations VALUES(59,'2026_10_21_170607_create_likes_table_and_migrate_existing_likes',2);
INSERT INTO migrations VALUES(60,'2026_10_21_172154_add_type_icon_order_to_categories_table',2);
INSERT INTO migrations VALUES(61,'2026_10_21_172155_drop_category_foreign_keys_on_documents_trainings_events',2);
INSERT INTO migrations VALUES(62,'2026_10_21_172156_migrate_category_tables_data_into_categories',2);
INSERT INTO migrations VALUES(63,'2026_10_21_172157_restore_category_foreign_keys_pointing_to_categories',2);
INSERT INTO migrations VALUES(64,'2026_10_21_172158_drop_old_category_tables',2);
INSERT INTO migrations VALUES(65,'2026_10_22_094655_make_categories_slug_unique_per_type',3);
INSERT INTO migrations VALUES(66,'2026_10_22_110542_drop_council_members_table',4);
