-- Fix HostForge users: id must be AUTO_INCREMENT (error 1364).
-- Safe to re-run: MODIFY keeps existing rows and only restores AUTO_INCREMENT.
ALTER TABLE `sms2_users`
  MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
