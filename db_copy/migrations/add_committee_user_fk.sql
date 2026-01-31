-- Migration: Add foreign key constraint to committee.user_id
-- Date: January 2026
-- Purpose: Ensure referential integrity between committee and users tables
--
-- Pre-migration check (run manually):
-- SELECT c.* FROM committee c LEFT JOIN users u ON c.user_id = u.user_id WHERE u.user_id IS NULL;
-- If any rows returned, fix orphaned records before running migration.

-- Add the foreign key constraint
ALTER TABLE `committee`
  ADD CONSTRAINT `fk_committee_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users`(`user_id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

-- Notes:
-- ON DELETE RESTRICT: Prevents deleting a user who has a committee role
-- ON UPDATE CASCADE: If user_id changes (rare), committee record updates automatically
-- To delete a user with committee role, first reassign or delete their committee role
