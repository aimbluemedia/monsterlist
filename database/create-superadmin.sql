-- Creates the first superadmin account.
-- Login: super@monsterlist.org / ChangeMe-2026!
-- ⚠️ CHANGE BOTH the email below (before importing) and the password
--    (immediately after first login, at /account/settings).
INSERT INTO users (email, password_hash, name, role)
VALUES ('super@monsterlist.org', '$2y$12$FDk1Aks.zLIkkAyhl5zmeelJhn4hP2Gsa7b1zHNiQpTzO3qmalA8i', 'Super Admin', 'superadmin');
