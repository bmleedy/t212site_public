-- Migration: Add notification preferences column to users table
-- Created: 2026-01-05
-- Description: Adds a VARCHAR(255) column to store user notification preferences as JSON

-- Add the notif_preferences column
ALTER TABLE users
ADD COLUMN notif_preferences VARCHAR(255) DEFAULT NULL
COMMENT 'JSON object storing user notification preferences';

-- Note: NULL default means user is opted INTO all notifications (per requirement #2)
