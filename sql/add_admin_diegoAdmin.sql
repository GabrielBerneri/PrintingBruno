-- Crea o actualiza el usuario diegoAdmin.
-- Password plano solicitado: INGprg2706!
-- Hash generado con password_hash(PASSWORD_DEFAULT)

INSERT INTO admin_users (username, email, password_hash)
VALUES (
  'diegoAdmin',
  NULL,
  '$2y$10$Dj3HoIadt.zm4i9MUxZcVuSTuKv6Pt9hDPrsr5jB2ZUi6GLMXa54C'
)
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash);
