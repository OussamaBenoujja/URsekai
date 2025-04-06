-- URSEKAI GAMING PLATFORM DATABASE CREATION SCRIPT
-- This script creates a comprehensive database structure for the URSEKAI gaming platform

-- Drop database if it exists (comment this out in production)
DROP DATABASE IF EXISTS ursekai_db;

-- Create database with UTF-8 character set
CREATE DATABASE ursekai_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the newly created database
USE ursekai_db;

-- ------------------------------------------
-- CORE TABLES (Fewest Dependencies)
-- ------------------------------------------

-- Table: users
-- Stores core user information for both players and developers
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    display_name VARCHAR(50),
    avatar_url VARCHAR(255),
    bio TEXT,
    country VARCHAR(50),
    city VARCHAR(50),
    date_of_birth DATE,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login_date DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    is_email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    role ENUM('player', 'developer', 'moderator', 'admin') DEFAULT 'player',
    account_level INT DEFAULT 1,
    experience_points INT DEFAULT 0,
    total_playtime_minutes INT DEFAULT 0,
    preferred_language VARCHAR(10) DEFAULT 'en',
    theme_preference ENUM('light', 'dark', 'system') DEFAULT 'system',
    notification_preferences JSON,
    privacy_settings JSON,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_banned BOOLEAN DEFAULT FALSE,
    ban_reason TEXT,
    ban_expires DATETIME,
    last_password_change DATETIME,
    required_password_change BOOLEAN DEFAULT FALSE,
    login_attempts INT DEFAULT 0,
    account_locked_until DATETIME,
    referral_code VARCHAR(20),
    referred_by INT,
    FOREIGN KEY (referred_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: developers
-- Extends user information for developers with additional attributes
CREATE TABLE developers (
    developer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    company_name VARCHAR(100),
    website VARCHAR(255),
    logo_url VARCHAR(255),
    description TEXT,
    founding_date DATE,
    team_size INT,
    verified BOOLEAN DEFAULT FALSE,
    verification_date DATETIME,
    developer_level INT DEFAULT 1,
    total_games_published INT DEFAULT 0,
    total_downloads INT DEFAULT 0,
    payout_email VARCHAR(100),
    stripe_account_id VARCHAR(100),
    paypal_email VARCHAR(100),
    tax_id VARCHAR(50),
    bank_account_info TEXT,
    developer_agreement_signed BOOLEAN DEFAULT FALSE,
    agreement_signed_date DATETIME,
    revenue_share_percentage DECIMAL(5,2) DEFAULT 70.00,
    custom_developer_page BOOLEAN DEFAULT FALSE,
    custom_page_theme JSON,
    api_key VARCHAR(255),
    api_key_created DATETIME,
    api_key_last_used DATETIME,
    webhook_url VARCHAR(255),
    webhook_secret VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: game_categories
-- Available categories for games
CREATE TABLE game_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon_url VARCHAR(255),
    display_order INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: game_tags
-- Tags for categorizing and searching games
CREATE TABLE game_tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- Table: currencies
-- Virtual currencies used in the platform
CREATE TABLE currencies (
    currency_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    exchange_rate_to_usd DECIMAL(10,6),
    is_premium BOOLEAN DEFAULT FALSE,
    is_tradable BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: subscriptions
-- Subscription plans
CREATE TABLE subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    billing_cycle ENUM('monthly', 'quarterly', 'biannually', 'annually') DEFAULT 'monthly',
    duration_days INT,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: badges
-- Badges for platform-wide achievements
CREATE TABLE badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    points INT DEFAULT 10,
    prerequisite_badges JSON,
    unlock_criteria TEXT,
    is_hidden BOOLEAN DEFAULT FALSE,
    is_limited_time BOOLEAN DEFAULT FALSE,
    available_from DATETIME,
    available_until DATETIME,
    total_awarded INT DEFAULT 0,
    max_awards INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

-- Table: forums
-- Forum categories
CREATE TABLE forums (
    forum_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon_url VARCHAR(255),
    display_order INT,
    parent_forum_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    is_private BOOLEAN DEFAULT FALSE,
    required_role ENUM('player', 'developer', 'moderator', 'admin'),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    total_threads INT DEFAULT 0,
    total_posts INT DEFAULT 0,
    last_post_id INT, -- FK added later
    FOREIGN KEY (parent_forum_id) REFERENCES forums(forum_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: analytics_platform_metrics
-- Daily aggregated platform metrics
CREATE TABLE analytics_platform_metrics (
    metric_id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    total_games INT DEFAULT 0,
    new_games INT DEFAULT 0,
    total_plays INT DEFAULT 0,
    total_playtime_minutes INT DEFAULT 0,
    total_transactions INT DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    platform_revenue DECIMAL(15,2) DEFAULT 0,
    developer_payouts DECIMAL(15,2) DEFAULT 0,
    average_session_duration_minutes DECIMAL(10,2) DEFAULT 0,
    peak_concurrent_users INT DEFAULT 0,
    forum_posts INT DEFAULT 0,
    chat_messages INT DEFAULT 0,
    reviews_posted INT DEFAULT 0,
    support_tickets_opened INT DEFAULT 0,
    support_tickets_resolved INT DEFAULT 0,
    browser_data JSON,
    device_data JSON,
    country_data JSON,
    referrer_data JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (date)
) ENGINE=InnoDB;

-- Table: cache
-- InnoDB table for caching
CREATE TABLE cache (
    id VARCHAR(255) PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL,
    value BLOB NOT NULL,
    expiration INT NOT NULL,
    INDEX key_index (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------
-- TABLES DEPENDING ON users
-- ------------------------------------------

-- Table: private_messages
-- Direct messages between users
CREATE TABLE private_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_type ENUM('text', 'image', 'voice', 'video', 'game_invite') DEFAULT 'text',
    content TEXT,
    attachment_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at DATETIME,
    is_deleted_by_sender BOOLEAN DEFAULT FALSE,
    is_deleted_by_receiver BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: friends
-- Tracks user friendships
CREATE TABLE friends (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'blocked') DEFAULT 'pending',
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME,
    declined_at DATETIME,
    blocked_at DATETIME,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, friend_id)
) ENGINE=InnoDB;

-- Table: user_wallets
-- Tracks virtual currency balances for users
CREATE TABLE user_wallets (
    wallet_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    currency_id INT NOT NULL,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    lifetime_earned DECIMAL(15,2) NOT NULL DEFAULT 0,
    lifetime_spent DECIMAL(15,2) NOT NULL DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(currency_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, currency_id)
) ENGINE=InnoDB;

-- Table: user_activity_log
-- Detailed log of user activities
CREATE TABLE user_activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    related_id INT,
    related_type VARCHAR(50),
    session_id VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: notifications
-- System notifications for users
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    link VARCHAR(255),
    icon VARCHAR(50),
    related_id INT,
    related_type VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: email_queue
-- Queue for outgoing emails
CREATE TABLE email_queue (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email_address VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    html_body TEXT,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    last_retry DATETIME,
    scheduled_for DATETIME,
    email_type VARCHAR(50),
    template_id VARCHAR(100),
    template_data JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: reports
-- User-submitted reports for content moderation
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT,
    content_type ENUM('user', 'game', 'review', 'forum_post', 'chat_message', 'group_post', 'comment') NOT NULL,
    content_id INT NOT NULL,
    reason ENUM('inappropriate', 'spam', 'harassment', 'illegal', 'violence', 'hate_speech', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'investigating', 'actioned', 'rejected', 'auto_actioned') DEFAULT 'pending',
    moderator_id INT,
    action_taken TEXT,
    action_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (moderator_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: moderation_log
-- Audit trail for moderation actions
CREATE TABLE moderation_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    moderator_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    affected_user_id INT,
    content_type VARCHAR(50),
    content_id INT,
    reason TEXT,
    previous_state JSON,
    new_state JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (moderator_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (affected_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: content_filters
-- Automated filtering rules for content
CREATE TABLE content_filters (
    filter_id INT AUTO_INCREMENT PRIMARY KEY,
    filter_type ENUM('keyword', 'regex', 'user', 'ip', 'domain') NOT NULL,
    value TEXT NOT NULL,
    action ENUM('flag', 'block', 'replace', 'notify', 'shadow_ban') NOT NULL,
    replacement TEXT,
    context ENUM('all', 'chat', 'forum', 'username', 'game', 'review') DEFAULT 'all',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    hit_count INT DEFAULT 0,
    last_hit DATETIME,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    notes TEXT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: feedback
-- General user feedback about the platform
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    feedback_type ENUM('bug', 'feature_request', 'suggestion', 'complaint', 'praise') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('new', 'reviewing', 'planned', 'in_progress', 'completed', 'declined') DEFAULT 'new',
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    assigned_to INT,
    is_public BOOLEAN DEFAULT FALSE,
    upvotes INT DEFAULT 0,
    response TEXT,
    responded_by INT,
    responded_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: system_settings
-- Global system configuration settings
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    value TEXT,
    data_type ENUM('string', 'integer', 'float', 'boolean', 'json', 'date', 'datetime') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY (category, name)
) ENGINE=InnoDB;

-- Table: maintenance_log
-- Record of system maintenance and updates
CREATE TABLE maintenance_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME,
    status ENUM('scheduled', 'in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'scheduled',
    affected_systems TEXT,
    performed_by INT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: user_social_connections
-- Tracks user's social media accounts for login and sharing
CREATE TABLE user_social_connections (
    connection_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider ENUM('google', 'facebook', 'twitter', 'discord', 'twitch') NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    provider_username VARCHAR(100),
    access_token VARCHAR(255),
    refresh_token VARCHAR(255),
    token_expires DATETIME,
    profile_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used DATETIME,
    is_primary_login BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, provider)
) ENGINE=InnoDB;

-- Table: sessions
-- Tracks user sessions and login information
CREATE TABLE sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    last_activity DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: user_badges
-- Tracks which users have earned which badges
CREATE TABLE user_badges (
    user_badge_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at DATETIME NOT NULL,
    awarded_by INT,
    is_featured BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE,
    FOREIGN KEY (awarded_by) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY (user_id, badge_id)
) ENGINE=InnoDB;

-- ------------------------------------------
-- TABLES DEPENDING ON users, developers, games, etc.
-- ------------------------------------------

-- Table: games
-- Game metadata and information
CREATE TABLE games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    developer_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(255),
    thumbnail_url VARCHAR(255),
    banner_url VARCHAR(255),
    logo_url VARCHAR(255),
    main_category_id INT,
    release_date DATETIME,
    last_updated DATETIME,
    version VARCHAR(20),
    is_published BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    approval_date DATETIME,
    approved_by INT,
    rejection_reason TEXT,
    age_rating ENUM('E', 'E10+', 'T', 'M', 'A') DEFAULT 'E',
    average_rating DECIMAL(3,2),
    total_ratings INT DEFAULT 0,
    total_plays INT DEFAULT 0,
    total_unique_players INT DEFAULT 0,
    total_playtime_minutes INT DEFAULT 0,
    monetization_type ENUM('free', 'premium', 'freemium', 'ads', 'subscription') DEFAULT 'free',
    price DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    sale_price DECIMAL(10,2),
    sale_starts DATETIME,
    sale_ends DATETIME,
    supports_fullscreen BOOLEAN DEFAULT TRUE,
    supports_mobile BOOLEAN DEFAULT FALSE,
    minimum_browser_requirements JSON,
    recommended_browser_requirements JSON,
    privacy_policy_url VARCHAR(255),
    terms_of_service_url VARCHAR(255),
    support_email VARCHAR(100),
    support_url VARCHAR(255),
    game_instructions TEXT,
    game_controls TEXT,
    has_multiplayer BOOLEAN DEFAULT FALSE,
    max_players INT DEFAULT 1,
    has_leaderboard BOOLEAN DEFAULT FALSE,
    has_achievements BOOLEAN DEFAULT FALSE,
    has_in_app_purchases BOOLEAN DEFAULT FALSE,
    has_ads BOOLEAN DEFAULT FALSE,
    custom_css TEXT,
    custom_javascript TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON DELETE CASCADE,
    FOREIGN KEY (main_category_id) REFERENCES game_categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: groups
-- User groups/communities
CREATE TABLE `groups` (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    logo_url VARCHAR(255),
    banner_url VARCHAR(255),
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    is_public BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    is_official BOOLEAN DEFAULT FALSE,
    group_type ENUM('general', 'game', 'developer', 'event') DEFAULT 'general',
    game_id INT,
    developer_id INT,
    member_count INT DEFAULT 1,
    post_count INT DEFAULT 0,
    rules TEXT,
    discord_url VARCHAR(255),
    website_url VARCHAR(255),
    custom_css TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE SET NULL,
    FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: chat_rooms
-- Chat rooms for real-time communication
CREATE TABLE chat_rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    room_type ENUM('global', 'game', 'group', 'private') NOT NULL,
    game_id INT,
    group_id INT,
    created_by INT,
    is_private BOOLEAN DEFAULT FALSE,
    max_users INT,
    is_voice_enabled BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: chat_messages
-- Messages sent in chat rooms
CREATE TABLE chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message_type ENUM('text', 'image', 'voice', 'video', 'system', 'game_invite') DEFAULT 'text',
    content TEXT,
    attachment_url VARCHAR(255),
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at DATETIME,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at DATETIME,
    deleted_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_by JSON,
    reactions JSON,
    reply_to_message_id INT,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (deleted_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: chat_room_members
-- Tracks users in chat rooms
CREATE TABLE chat_room_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_muted BOOLEAN DEFAULT FALSE,
    mute_expires DATETIME,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (room_id, user_id)
) ENGINE=InnoDB;

-- Table: transactions
-- Tracks all monetary transactions
CREATE TABLE transactions (
    transaction_id VARCHAR(36) PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'refund', 'gift', 'reward', 'subscription', 'donation') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'refunded', 'disputed') DEFAULT 'pending',
    payment_method ENUM('credit_card', 'paypal', 'crypto', 'bank_transfer', 'platform_credit', 'other') NOT NULL,
    payment_details JSON,
    platform_fee DECIMAL(10,2),
    developer_cut DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME,
    reference_id VARCHAR(100),
    game_id INT,
    developer_id INT,
    item_id INT, -- FK added later
    subscription_id INT, -- FK added later
    is_test BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE SET NULL,
    FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: virtual_items
-- Digital items that can be purchased
CREATE TABLE virtual_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    item_type ENUM('consumable', 'durable', 'collectible', 'currency', 'subscription') NOT NULL,
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    price DECIMAL(10,2),
    currency_id INT,
    is_on_sale BOOLEAN DEFAULT FALSE,
    sale_price DECIMAL(10,2),
    sale_starts DATETIME,
    sale_ends DATETIME,
    quantity_available INT,
    quantity_sold INT DEFAULT 0,
    max_per_user INT,
    is_tradable BOOLEAN DEFAULT FALSE,
    trade_cooldown_hours INT DEFAULT 0,
    is_giftable BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(currency_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: analytics_game_metrics
-- Daily aggregated game metrics for analytics
CREATE TABLE analytics_game_metrics (
    metric_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    date DATE NOT NULL,
    total_plays INT DEFAULT 0,
    unique_players INT DEFAULT 0,
    new_players INT DEFAULT 0,
    average_playtime_minutes DECIMAL(10,2) DEFAULT 0,
    total_playtime_minutes INT DEFAULT 0,
    completions INT DEFAULT 0,
    conversion_rate DECIMAL(5,2),
    revenue DECIMAL(10,2) DEFAULT 0,
    ad_impressions INT DEFAULT 0,
    ad_clicks INT DEFAULT 0,
    ad_revenue DECIMAL(10,2) DEFAULT 0,
    ratings_count INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    shares INT DEFAULT 0,
    achievement_unlocks INT DEFAULT 0,
    level_ups INT DEFAULT 0,
    in_app_purchases INT DEFAULT 0,
    peak_concurrent_players INT DEFAULT 0,
    browser_data JSON,
    device_data JSON,
    country_data JSON,
    referrer_data JSON,
    retention_data JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    UNIQUE KEY (game_id, date)
) ENGINE=InnoDB;

-- Table: support_tickets
-- User support tickets
CREATE TABLE support_tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'waiting_for_user', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    category VARCHAR(50) NOT NULL,
    assigned_to INT,
    game_id INT,
    developer_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    resolution_notes TEXT,
    satisfaction_rating INT,
    feedback TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE SET NULL,
    FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: api_logs
-- Logs of API access and usage
CREATE TABLE api_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    developer_id INT NOT NULL,
    api_key VARCHAR(255),
    endpoint VARCHAR(255) NOT NULL,
    method ENUM('GET', 'POST', 'PUT', 'DELETE', 'PATCH') NOT NULL,
    request_headers TEXT,
    request_body TEXT,
    response_code INT,
    response_body TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    execution_time_ms INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed', 'throttled', 'unauthorized') NOT NULL,
    error_message TEXT,
    FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: webhooks
-- Developer-configured webhooks
CREATE TABLE webhooks (
    webhook_id INT AUTO_INCREMENT PRIMARY KEY,
    developer_id INT NOT NULL,
    game_id INT,
    url VARCHAR(255) NOT NULL,
    events JSON NOT NULL,
    secret_key VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    last_triggered DATETIME,
    failure_count INT DEFAULT 0,
    last_failure_message TEXT,
    last_failure_time DATETIME,
    FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: game_categories_mapping
-- Many-to-many mapping for games and categories
CREATE TABLE game_categories_mapping (
    mapping_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES game_categories(category_id) ON DELETE CASCADE,
    UNIQUE KEY (game_id, category_id)
) ENGINE=InnoDB;

-- Table: game_tags_mapping
-- Many-to-many mapping for games and tags
CREATE TABLE game_tags_mapping (
    mapping_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES game_tags(tag_id) ON DELETE CASCADE,
    UNIQUE KEY (game_id, tag_id)
) ENGINE=InnoDB;

-- Table: game_assets
-- Stores WebGL game files and resources
CREATE TABLE game_assets (
    asset_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    asset_type ENUM('main_game', 'texture', 'sound', 'model', 'script', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    file_extension VARCHAR(10),
    mime_type VARCHAR(100),
    checksum VARCHAR(64),
    version VARCHAR(20),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_compressed BOOLEAN DEFAULT FALSE,
    width INT,
    height INT,
    duration INT,
    is_active BOOLEAN DEFAULT TRUE,
    cdn_url VARCHAR(255),
    metadata JSON,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: game_screenshots
-- Stores screenshots for game listings
CREATE TABLE game_screenshots (
    screenshot_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    caption VARCHAR(255),
    width INT,
    height INT,
    display_order INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: game_videos
-- Stores videos for game trailers, etc.
CREATE TABLE game_videos (
    video_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    video_type ENUM('trailer', 'gameplay', 'teaser', 'tutorial', 'other') DEFAULT 'trailer',
    title VARCHAR(100),
    description TEXT,
    video_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    duration_seconds INT,
    width INT,
    height INT,
    display_order INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: game_playtime
-- Tracks each play session for analytics
CREATE TABLE game_playtime (
    playtime_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT,
    session_id VARCHAR(255),
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    duration_minutes INT,
    is_complete BOOLEAN DEFAULT FALSE,
    device_type VARCHAR(50),
    browser VARCHAR(50),
    operating_system VARCHAR(50),
    screen_resolution VARCHAR(20),
    ip_address VARCHAR(45),
    country VARCHAR(50),
    city VARCHAR(50),
    game_version VARCHAR(20),
    events_data JSON,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: game_favorites
-- Tracks user favorites/bookmarks
CREATE TABLE game_favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, game_id)
) ENGINE=InnoDB;

-- Table: achievements
-- Achievement definitions for games
CREATE TABLE achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_url VARCHAR(255),
    points INT DEFAULT 10,
    difficulty ENUM('easy', 'medium', 'hard', 'extreme') DEFAULT 'medium',
    is_hidden BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    unlock_criteria TEXT,
    total_unlocks INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: user_achievements
-- Tracks which users have unlocked which achievements
CREATE TABLE user_achievements (
    user_achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at DATETIME NOT NULL,
    game_state_data JSON,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(achievement_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, achievement_id)
) ENGINE=InnoDB;

-- Table: game_levels
-- Defines levels and progression within games
CREATE TABLE game_levels (
    level_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    level_number INT NOT NULL,
    name VARCHAR(100),
    description TEXT,
    difficulty ENUM('tutorial', 'easy', 'medium', 'hard', 'extreme') DEFAULT 'medium',
    xp_reward INT,
    currency_reward INT,
    unlock_criteria TEXT,
    is_hidden BOOLEAN DEFAULT FALSE,
    icon_url VARCHAR(255),
    thumbnail_url VARCHAR(255),
    time_limit_seconds INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    UNIQUE KEY (game_id, level_number)
) ENGINE=InnoDB;

-- Table: user_game_progress
-- Tracks user progress in each game
CREATE TABLE user_game_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    current_level INT DEFAULT 1,
    highest_level_reached INT DEFAULT 1,
    total_score BIGINT DEFAULT 0,
    highest_score BIGINT DEFAULT 0,
    total_time_played_minutes INT DEFAULT 0,
    xp_earned INT DEFAULT 0,
    in_game_currency INT DEFAULT 0,
    achievements_unlocked INT DEFAULT 0,
    total_achievements INT DEFAULT 0,
    game_specific_data JSON,
    last_played DATETIME,
    first_played DATETIME,
    times_played INT DEFAULT 1,
    save_data LONGTEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, game_id)
) ENGINE=InnoDB;

-- Table: game_reviews
-- User reviews for games
CREATE TABLE game_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    rating DECIMAL(3,1) NOT NULL,
    title VARCHAR(100),
    content TEXT,
    has_spoilers BOOLEAN DEFAULT FALSE,
    playtime_at_review_minutes INT,
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_verified_player BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    hide_reason TEXT,
    device_type VARCHAR(50),
    browser VARCHAR(50),
    operating_system VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, game_id)
) ENGINE=InnoDB;

-- Table: leaderboards
-- Defines leaderboards for games
CREATE TABLE leaderboards (
    leaderboard_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    score_type ENUM('points', 'time', 'distance', 'kills', 'custom') DEFAULT 'points',
    sort_order ENUM('ascending', 'descending') DEFAULT 'descending',
    reset_frequency ENUM('never', 'daily', 'weekly', 'monthly', 'seasonally') DEFAULT 'never',
    last_reset DATETIME,
    next_reset DATETIME,
    is_global BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    max_entries INT,
    display_entries INT DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: forum_threads
-- Discussion threads within forums
CREATE TABLE forum_threads (
    thread_id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT NOT NULL,
    user_id INT NOT NULL,
    game_id INT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_sticky BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    hide_reason TEXT,
    views INT DEFAULT 0,
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    last_post_at DATETIME,
    last_post_id INT,
    last_poster_id INT,
    total_posts INT DEFAULT 1,
    FOREIGN KEY (forum_id) REFERENCES forums(forum_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE SET NULL,
    UNIQUE KEY (forum_id, slug)
) ENGINE=InnoDB;

-- Table: forum_posts
-- Individual posts within forum threads
CREATE TABLE forum_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_solution BOOLEAN DEFAULT FALSE,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at DATETIME,
    edited_by INT,
    is_hidden BOOLEAN DEFAULT FALSE,
    hide_reason TEXT,
    hidden_by INT,
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (thread_id) REFERENCES forum_threads(thread_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (hidden_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: group_posts
-- Posts made within groups
CREATE TABLE group_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_post_id INT,
    content TEXT NOT NULL,
    attachment_url VARCHAR(255),
    attachment_type VARCHAR(50),
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_announcement BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    hide_reason TEXT,
    hidden_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES `groups`(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_post_id) REFERENCES group_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (hidden_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: forum_post_votes
-- Tracks upvotes/downvotes on forum posts
CREATE TABLE forum_post_votes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (post_id, user_id)
) ENGINE=InnoDB;

-- Table: leaderboard_entries
-- Individual entries/scores for leaderboards
CREATE TABLE leaderboard_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    leaderboard_id INT NOT NULL,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    score BIGINT NOT NULL,
    metadata JSON,
    `rank` INT,
    submission_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT TRUE,
    invalidation_reason TEXT,
    ip_address VARCHAR(45),
    browser VARCHAR(50),
    device_type VARCHAR(50),
    FOREIGN KEY (leaderboard_id) REFERENCES leaderboards(leaderboard_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: group_members
-- Tracks users in groups
CREATE TABLE group_members (
    group_member_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'admin', 'user') DEFAULT 'user',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (group_id, user_id)
) ENGINE=InnoDB;

-- ------------------------------------------
-- FINAL UPDATES (Adding FKs deferred earlier)
-- ------------------------------------------

ALTER TABLE transactions
ADD CONSTRAINT fk_transactions_item_id FOREIGN KEY (item_id) REFERENCES virtual_items(item_id) ON DELETE SET NULL,
ADD CONSTRAINT fk_transactions_subscription_id FOREIGN KEY (subscription_id) REFERENCES subscriptions(subscription_id) ON DELETE SET NULL;

ALTER TABLE forums
ADD CONSTRAINT fk_forums_last_post_id FOREIGN KEY (last_post_id) REFERENCES forum_posts(post_id) ON DELETE SET NULL;

ALTER TABLE forum_threads
ADD CONSTRAINT fk_forum_threads_last_post_id FOREIGN KEY (last_post_id) REFERENCES forum_posts(post_id) ON DELETE SET NULL,
ADD CONSTRAINT fk_forum_threads_last_poster_id FOREIGN KEY (last_poster_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- ------------------------------------------
-- ADDITIONAL INDEXES FOR OPTIMIZATION
-- ------------------------------------------

-- Indexes for search optimization
CREATE INDEX idx_games_title ON games(title);
CREATE INDEX idx_games_release_date ON games(release_date);
CREATE INDEX idx_games_average_rating ON games(average_rating);
CREATE INDEX idx_games_total_plays ON games(total_plays);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_last_login ON users(last_login_date);
CREATE INDEX idx_forum_threads_created_at ON forum_threads(created_at);
CREATE INDEX idx_forum_posts_created_at ON forum_posts(created_at);
CREATE INDEX idx_game_reviews_rating ON game_reviews(rating);
CREATE INDEX idx_game_reviews_created_at ON game_reviews(created_at);
CREATE INDEX idx_leaderboard_entries_score ON leaderboard_entries(score);
CREATE INDEX idx_chat_messages_created_at ON chat_messages(created_at);
CREATE INDEX idx_private_messages_created_at ON private_messages(created_at);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_support_tickets_status ON support_tickets(status);
CREATE INDEX idx_support_tickets_created_at ON support_tickets(created_at);
CREATE INDEX idx_user_activity_log_created_at ON user_activity_log(created_at);
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_reports_status ON reports(status);
CREATE INDEX idx_api_logs_created_at ON api_logs(created_at);

-- Full-text search indexes
CREATE FULLTEXT INDEX ft_games_title_description ON games(title, description, short_description);
CREATE FULLTEXT INDEX ft_forum_threads_title_content ON forum_threads(title, content);
CREATE FULLTEXT INDEX ft_forum_posts_content ON forum_posts(content);
CREATE FULLTEXT INDEX ft_game_reviews_title_content ON game_reviews(title, content);

-- ------------------------------------------
-- INITIAL DATA POPULATION
-- ------------------------------------------

-- Insert default system settings
INSERT INTO system_settings (category, name, value, data_type, description, is_public) VALUES
('general', 'site_name', 'URSEKAI', 'string', 'The name of the platform', true),
('general', 'site_description', 'A unified platform for WebGL games', 'string', 'Brief description of the platform', true),
('general', 'maintenance_mode', 'false', 'boolean', 'Whether the site is in maintenance mode', true),
('general', 'contact_email', 'support@ursekai.com', 'string', 'Primary contact email', true),
('security', 'max_login_attempts', '5', 'integer', 'Maximum number of login attempts before account lockout', false),
('security', 'account_lockout_duration_minutes', '30', 'integer', 'Duration of account lockout in minutes', false),
('security', 'password_reset_expiry_hours', '24', 'integer', 'Hours until password reset token expires', false),
('game', 'max_game_file_size_mb', '100', 'integer', 'Maximum file size for game uploads in MB', true),
('game', 'allowed_game_file_types', 'json', 'string', 'Allowed file extensions for game uploads', true),
('user', 'default_avatar_url', '/assets/images/default_avatar.png', 'string', 'Default avatar for new users', true),
('user', 'registration_enabled', 'true', 'boolean', 'Whether new user registration is enabled', true);

-- Insert default game categories
INSERT INTO game_categories (name, description, display_order, is_active) VALUES
('Action', 'Fast-paced games requiring quick reflexes', 1, true),
('Adventure', 'Story-driven exploration games', 2, true),
('Puzzle', 'Brain teasers and logic puzzles', 3, true),
('RPG', 'Role-playing games with character development', 4, true),
('Strategy', 'Games focused on planning and strategic thinking', 5, true),
('Simulation', 'Games that simulate real-world activities', 6, true),
('Sports', 'Athletic competitions and sports games', 7, true),
('Racing', 'Vehicle racing games', 8, true),
('Platformer', 'Games involving jumping between platforms', 9, true),
('Shooter', 'Games focusing on shooting enemies', 10, true),
('Multiplayer', 'Games designed for multiple players', 11, true),
('Casual', 'Simple games for quick play sessions', 12, true);

-- Insert common game tags
INSERT INTO game_tags (name, description, is_active) VALUES
('2D', 'Two-dimensional games', true),
('3D', 'Three-dimensional games', true),
('Pixel Art', 'Games with pixel art style graphics', true),
('Story-Rich', 'Games with deep storytelling', true),
('Roguelike', 'Games with procedurally generated levels', true),
('Open World', 'Games with large, open environments', true),
('First-Person', 'Games played from first-person perspective', true),
('Third-Person', 'Games played from third-person perspective', true),
('Top-Down', 'Games with a top-down view', true),
('Side-Scroller', 'Games where the player moves sideways', true),
('Single-Player', 'Games designed for one player', true),
('Co-op', 'Games where players work together', true),
('PvP', 'Player versus player games', true),
('Fantasy', 'Games set in fantasy worlds', true),
('Sci-Fi', 'Science fiction themed games', true),
('Horror', 'Scary or horror-themed games', true),
('Survival', 'Games focused on surviving harsh conditions', true),
('Stealth', 'Games emphasizing sneaking and avoiding detection', true),
('Educational', 'Games designed to teach or educate', true),
('Relaxing', 'Low-stress games designed for relaxation', true);

-- Insert default badges
INSERT INTO badges (name, description, icon_url, category, points, is_hidden, is_active) VALUES
('Early Adopter', 'Joined during the platform\'s launch period', '/assets/badges/early_adopter.png', 'Platform', 50, false, true),
('Game Master', 'Played 100 different games', '/assets/badges/game_master.png', 'Gaming', 100, false, true),
('Social Butterfly', 'Made friends with 50 other users', '/assets/badges/social_butterfly.png', 'Social', 75, false, true),
('Critic', 'Wrote 25 game reviews', '/assets/badges/critic.png', 'Community', 50, false, true),
('Developer', 'Published your first game', '/assets/badges/developer.png', 'Development', 150, false, true),
('Community Moderator', 'Helped moderate the community', '/assets/badges/moderator.png', 'Community', 100, true, true),
('Bug Hunter', 'Reported significant bugs that were fixed', '/assets/badges/bug_hunter.png', 'Platform', 60, false, true),
('Achievement Hunter', 'Unlocked 1000 achievements across all games', '/assets/badges/achievement_hunter.png', 'Gaming', 200, false, true),
('Founder\'s Circle', 'One of the first 100 registered users', '/assets/badges/founders_circle.png', 'Platform', 250, false, true),
('Game Jam Winner', 'Won a platform-hosted game jam', '/assets/badges/game_jam_winner.png', 'Development', 300, false, true);

-- Insert virtual currencies
INSERT INTO currencies (code, name, description, exchange_rate_to_usd, is_premium, is_tradable, is_active) VALUES
('URSC', 'URSEKAI Coins', 'Standard platform currency used for transactions', 0.01, true, false, true),
('URGM', 'Game Gems', 'Earned through gameplay and achievements', NULL, false, true, true);

-- Insert subscription plans
INSERT INTO subscriptions (name, description, price, currency, billing_cycle, features, is_active, is_featured) VALUES
('URSEKAI Basic', 'Basic subscription with premium features', 4.99, 'USD', 'monthly', '{"ad_free": true, "monthly_coins": 500, "exclusive_badges": true}', true, false),
('URSEKAI Pro', 'Premium subscription with enhanced features', 9.99, 'USD', 'monthly', '{"ad_free": true, "monthly_coins": 1200, "exclusive_badges": true, "game_discounts": true, "early_access": true}', true, true),
('URSEKAI Developer', 'Subscription for game developers', 19.99, 'USD', 'monthly', '{"ad_free": true, "monthly_coins": 2000, "exclusive_badges": true, "game_discounts": true, "early_access": true, "reduced_platform_fees": true, "premium_analytics": true, "priority_support": true}', true, false),
('URSEKAI Annual', 'Annual subscription with the best value', 49.99, 'USD', 'annually', '{"ad_free": true, "monthly_coins": 1500, "exclusive_badges": true, "game_discounts": true, "early_access": true, "bonus_annual_coins": 5000}', true, true);

-- Create admin user (you should change this password in production!)
INSERT INTO users (username, email, password, first_name, last_name, display_name, role, is_active, is_email_verified, registration_date)
VALUES ('admin', 'admin@ursekai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'Admin', 'admin', true, true, NOW());

-- Create forums
INSERT INTO forums (name, description, slug, display_order, is_active) VALUES
('Announcements', 'Official platform announcements and news', 'announcements', 1, true),
('General Discussion', 'General discussion about the platform and games', 'general-discussion', 2, true),
('Game Development', 'Discussions related to developing WebGL games', 'game-development', 3, true),
('Game Showcase', 'Show off your games and get feedback', 'game-showcase', 4, true),
('Support', 'Get help with platform issues', 'support', 5, true),
('Suggestions', 'Share your ideas for improving the platform', 'suggestions', 6, true);

-- ------------------------------------------
-- STORED PROCEDURES AND TRIGGERS
-- ------------------------------------------

-- Procedure: update_game_ratings
DELIMITER $
CREATE PROCEDURE update_game_ratings(IN game_id_param INT)
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE total_count INT;

    SELECT AVG(rating), COUNT(*)
    INTO avg_rating, total_count
    FROM game_reviews
    WHERE game_id = game_id_param AND is_hidden = FALSE;

    UPDATE games
    SET average_rating = avg_rating,
        total_ratings = total_count
    WHERE game_id = game_id_param;
END $
DELIMITER ;

-- Trigger: after_review_insert
DELIMITER $
CREATE TRIGGER after_review_insert
AFTER INSERT ON game_reviews
FOR EACH ROW
BEGIN
    CALL update_game_ratings(NEW.game_id);
END $
DELIMITER ;

-- Trigger: after_review_update
DELIMITER $
CREATE TRIGGER after_review_update
AFTER UPDATE ON game_reviews
FOR EACH ROW
BEGIN
    CALL update_game_ratings(NEW.game_id);
END $
DELIMITER ;

-- Trigger: after_review_delete
DELIMITER $
CREATE TRIGGER after_review_delete
AFTER DELETE ON game_reviews
FOR EACH ROW
BEGIN
    CALL update_game_ratings(OLD.game_id);
END $
DELIMITER ;

-- Procedure: update_forum_statistics
DELIMITER $
CREATE PROCEDURE update_forum_statistics(IN forum_id_param INT)
BEGIN
    DECLARE thread_count INT;
    DECLARE post_count INT;
    DECLARE last_post INT;

    -- Count threads
    SELECT COUNT(*) INTO thread_count
    FROM forum_threads
    WHERE forum_id = forum_id_param AND is_hidden = FALSE;

    -- Count posts across all threads in forum
    SELECT COUNT(p.post_id) INTO post_count
    FROM forum_posts p
    JOIN forum_threads t ON p.thread_id = t.thread_id
    WHERE t.forum_id = forum_id_param AND p.is_hidden = FALSE AND t.is_hidden = FALSE;

    -- Find last post
    SELECT p.post_id INTO last_post
    FROM forum_posts p
    JOIN forum_threads t ON p.thread_id = t.thread_id
    WHERE t.forum_id = forum_id_param AND p.is_hidden = FALSE AND t.is_hidden = FALSE
    ORDER BY p.created_at DESC
    LIMIT 1;

    -- Update forum statistics
    UPDATE forums
    SET total_threads = thread_count,
        total_posts = post_count,
        last_post_id = last_post
    WHERE forum_id = forum_id_param;
END $
DELIMITER ;

-- Trigger: after_forum_post_insert
DELIMITER $
CREATE TRIGGER after_forum_post_insert
AFTER INSERT ON forum_posts
FOR EACH ROW
BEGIN
    DECLARE forum_id_val INT;

    -- Get forum ID from thread
    SELECT forum_id INTO forum_id_val
    FROM forum_threads
    WHERE thread_id = NEW.thread_id;

    -- Update thread's last post info
    UPDATE forum_threads
    SET last_post_at = NOW(),
        last_post_id = NEW.post_id,
        last_poster_id = NEW.user_id,
        total_posts = total_posts + 1
    WHERE thread_id = NEW.thread_id;

    -- Update forum statistics
    CALL update_forum_statistics(forum_id_val);
END $
DELIMITER ;

-- Procedure: record_game_play
DELIMITER $
CREATE PROCEDURE record_game_play(
    IN p_game_id INT,
    IN p_user_id INT,
    IN p_session_id VARCHAR(255),
    IN p_duration_minutes INT,
    IN p_device_type VARCHAR(50),
    IN p_browser VARCHAR(50),
    IN p_operating_system VARCHAR(50),
    IN p_screen_resolution VARCHAR(20),
    IN p_ip_address VARCHAR(45),
    IN p_country VARCHAR(50),
    IN p_city VARCHAR(50),
    IN p_game_version VARCHAR(20)
)
BEGIN
    -- Insert play record
    INSERT INTO game_playtime (
        game_id, user_id, session_id,
        start_time, end_time, duration_minutes,
        is_complete, device_type, browser,
        operating_system, screen_resolution, ip_address,
        country, city, game_version
    ) VALUES (
        p_game_id, p_user_id, p_session_id,
        DATE_SUB(NOW(), INTERVAL p_duration_minutes MINUTE), NOW(), p_duration_minutes,
        TRUE, p_device_type, p_browser,
        p_operating_system, p_screen_resolution, p_ip_address,
        p_country, p_city, p_game_version
    );

    -- Update game statistics
    UPDATE games
    SET total_plays = total_plays + 1,
        total_playtime_minutes = total_playtime_minutes + p_duration_minutes
    WHERE game_id = p_game_id;

    -- If user is logged in, update their stats
    IF p_user_id IS NOT NULL THEN
        -- Update game progression for user
        INSERT INTO user_game_progress (
            user_id, game_id, last_played, times_played, total_time_played_minutes
        ) VALUES (
            p_user_id, p_game_id, NOW(), 1, p_duration_minutes
        ) ON DUPLICATE KEY UPDATE
            last_played = NOW(),
            times_played = times_played + 1,
            total_time_played_minutes = total_time_played_minutes + p_duration_minutes;

        -- Update user playtime stats
        UPDATE users
        SET total_playtime_minutes = total_playtime_minutes + p_duration_minutes
        WHERE user_id = p_user_id;
    END IF;

    -- Check for unique player
    IF NOT EXISTS (
        SELECT 1 FROM game_playtime
        WHERE game_id = p_game_id AND user_id = p_user_id AND start_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ) AND p_user_id IS NOT NULL THEN
        -- Increment unique players
        UPDATE games
        SET total_unique_players = total_unique_players + 1
        WHERE game_id = p_game_id;
    END IF;
END $
DELIMITER ;

-- Create event to update daily analytics
DELIMITER $
CREATE EVENT daily_analytics_update
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY
DO
BEGIN
    -- Insert platform metrics
    INSERT INTO analytics_platform_metrics (
        date, total_users, new_users, active_users,
        total_games, new_games, total_plays,
        total_playtime_minutes, total_transactions, total_revenue
    )
    SELECT
        CURRENT_DATE - INTERVAL 1 DAY,
        (SELECT COUNT(*) FROM users),
        (SELECT COUNT(*) FROM users WHERE DATE(registration_date) = CURRENT_DATE - INTERVAL 1 DAY),
        (SELECT COUNT(DISTINCT user_id) FROM user_activity_log WHERE DATE(created_at) = CURRENT_DATE - INTERVAL 1 DAY),
        (SELECT COUNT(*) FROM games WHERE is_published = TRUE),
        (SELECT COUNT(*) FROM games WHERE DATE(release_date) = CURRENT_DATE - INTERVAL 1 DAY AND is_published = TRUE),
        (SELECT COUNT(*) FROM game_playtime WHERE DATE(start_time) = CURRENT_DATE - INTERVAL 1 DAY),
        (SELECT COALESCE(SUM(duration_minutes), 0) FROM game_playtime WHERE DATE(start_time) = CURRENT_DATE - INTERVAL 1 DAY),
        (SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURRENT_DATE - INTERVAL 1 DAY AND status = 'completed'),
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE DATE(created_at) = CURRENT_DATE - INTERVAL 1 DAY AND status = 'completed');

    -- Insert game metrics for each active game
    INSERT INTO analytics_game_metrics (
        game_id, date, total_plays, unique_players,
        average_playtime_minutes, total_playtime_minutes
    )
    SELECT
        g.game_id,
        CURRENT_DATE - INTERVAL 1 DAY,
        COUNT(*),
        COUNT(DISTINCT user_id),
        AVG(duration_minutes),
        SUM(duration_minutes)
    FROM game_playtime gp
    JOIN games g ON gp.game_id = g.game_id
    WHERE DATE(start_time) = CURRENT_DATE - INTERVAL 1 DAY
    GROUP BY g.game_id;

    -- Clean up old sessions
    DELETE FROM sessions WHERE expires_at < NOW();

    -- Reset daily login counters if needed
    -- Add other maintenance tasks here
END $
DELIMITER ;

-- ------------------------------------------
-- DATABASE MAINTENANCE VIEWS
-- ------------------------------------------

-- View: active_games
CREATE VIEW vw_active_games AS
SELECT
    g.game_id, g.title, g.description, g.developer_id, d.company_name,
    g.release_date, g.average_rating, g.total_plays, g.total_unique_players,
    g.total_playtime_minutes, g.monetization_type, g.price
FROM games g
JOIN developers d ON g.developer_id = d.developer_id
WHERE g.is_published = TRUE AND g.is_approved = TRUE;

-- View: active_users
CREATE VIEW vw_active_users AS
SELECT
    u.user_id, u.username, u.email, u.display_name,
    u.registration_date, u.last_login_date, u.account_level,
    u.total_playtime_minutes,
    (SELECT COUNT(*) FROM user_achievements WHERE user_id = u.user_id) AS total_achievements,
    (SELECT COUNT(*) FROM user_badges WHERE user_id = u.user_id) AS total_badges,
    (SELECT COUNT(*) FROM game_reviews WHERE user_id = u.user_id) AS total_reviews,
    (SELECT COUNT(*) FROM forum_posts WHERE user_id = u.user_id) AS total_forum_posts
FROM users u
WHERE u.is_active = TRUE AND u.is_banned = FALSE;

-- View: developer_revenue
CREATE VIEW vw_developer_revenue AS
SELECT
    d.developer_id, d.user_id, u.username, d.company_name,
    g.game_id, g.title,
    COALESCE(SUM(t.amount), 0) AS total_revenue,
    COALESCE(SUM(t.developer_cut), 0) AS developer_revenue,
    COUNT(t.transaction_id) AS transaction_count
FROM developers d
JOIN users u ON d.user_id = u.user_id
JOIN games g ON d.developer_id = g.developer_id
LEFT JOIN transactions t ON g.game_id = t.game_id AND t.status = 'completed'
GROUP BY d.developer_id, g.game_id;

-- View: game_performance
CREATE VIEW vw_game_performance AS
SELECT
    g.game_id, g.title, g.developer_id, d.company_name,
    g.release_date, g.average_rating, g.total_ratings,
    g.total_plays, g.total_unique_players, g.total_playtime_minutes,
    COALESCE(
        (SELECT SUM(t.amount) FROM transactions t WHERE t.game_id = g.game_id AND t.status = 'completed'),
        0
    ) AS total_revenue,
    (SELECT COUNT(*) FROM achievements a WHERE a.game_id = g.game_id) AS achievement_count,
    (SELECT COUNT(*) FROM leaderboards l WHERE l.game_id = g.game_id) AS leaderboard_count
FROM games g
JOIN developers d ON g.developer_id = d.developer_id
WHERE g.is_published = TRUE;

-- ------------------------------------------
-- FINAL CONFIGURATION
-- ------------------------------------------

-- Set secure default values
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET GLOBAL max_connections = 500;
SET GLOBAL connect_timeout = 10;
SET GLOBAL interactive_timeout = 3600;
SET GLOBAL wait_timeout = 3600;
SET GLOBAL max_allowed_packet = 16777216;

-- END OF URSEKAI DATABASE CREATION SCRIPT
