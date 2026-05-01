-- Mario & Luigi — Database Schema
-- Import via Hostinger phpMyAdmin or MySQL CLI:
--   mysql -u USER -p DATABASE < database/schema.sql

-- ── Admin users ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`                  INT           NOT NULL AUTO_INCREMENT,
  `username`            VARCHAR(80)   NOT NULL,
  `email`               VARCHAR(120)  NOT NULL DEFAULT '',
  `password_hash`       VARCHAR(255)  NOT NULL,
  `role`                VARCHAR(30)   NOT NULL DEFAULT 'admin',
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at`       DATETIME          NULL DEFAULT NULL,
  `reset_token_hash`    VARCHAR(255)      NULL DEFAULT NULL,
  `reset_token_expires` DATETIME          NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOTE: if upgrading an existing database that already has admin_users without the
-- password-reset columns, run these statements manually once:
--   ALTER TABLE `admin_users` ADD COLUMN `reset_token_hash`    VARCHAR(255) NULL DEFAULT NULL;
--   ALTER TABLE `admin_users` ADD COLUMN `reset_token_expires` DATETIME     NULL DEFAULT NULL;

-- ── Login attempts (brute-force tracking) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `ip_address`    VARCHAR(45)   NOT NULL,
  `username`      VARCHAR(80)   NOT NULL DEFAULT '',
  `attempted_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time`   (`ip_address`, `attempted_at`),
  KEY `idx_user_time` (`username`,   `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Services ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `services` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(120)  NOT NULL,
  `description`   TEXT          NOT NULL,
  `price_text`    VARCHAR(80)   NOT NULL DEFAULT '',
  `badge_text`    VARCHAR(60)   NOT NULL DEFAULT '',
  `badge_color`   VARCHAR(30)   NOT NULL DEFAULT 'default',
  `whatsapp_link` VARCHAR(255)  NOT NULL DEFAULT '',
  `image_url`     VARCHAR(255)  NOT NULL DEFAULT '',
  `sort_order`    INT           NOT NULL DEFAULT 0,
  `active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Plans ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `plans` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(120)  NOT NULL,
  `description`   TEXT          NOT NULL,
  `price_text`    VARCHAR(80)   NOT NULL DEFAULT '',
  `features_json` TEXT          NOT NULL DEFAULT '[]',
  `featured`      TINYINT(1)    NOT NULL DEFAULT 0,
  `badge_text`    VARCHAR(60)   NOT NULL DEFAULT '',
  `whatsapp_link` VARCHAR(255)  NOT NULL DEFAULT '',
  `image_url`     VARCHAR(255)  NOT NULL DEFAULT '',
  `sort_order`    INT           NOT NULL DEFAULT 0,
  `active`        TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PIX config (singleton, id always = 1) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pix` (
  `id`            INT           NOT NULL,
  `pix_key`       VARCHAR(120)  NOT NULL DEFAULT '',
  `pix_hint_text` VARCHAR(255)  NOT NULL DEFAULT '',
  `whatsapp_link` VARCHAR(255)  NOT NULL DEFAULT '',
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `pix` (`id`, `pix_key`, `pix_hint_text`, `whatsapp_link`)
VALUES (1, '', 'Chave PIX (telefone):', '');

-- ── Contact messages ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contacts` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(120) NOT NULL,
  `telefone`   VARCHAR(30)  NOT NULL,
  `mensagem`   TEXT         NOT NULL,
  `ip_address` VARCHAR(45)  NOT NULL DEFAULT '',
  `read_at`    DATETIME         NULL DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Site settings (key-value) ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `site_settings` (
  `key`        VARCHAR(60)  NOT NULL,
  `value`      TEXT         NOT NULL DEFAULT '',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



INSERT IGNORE INTO `site_settings` (`key`, `value`) VALUES
  ('site_name',            'DEVS_POTENCIAL'),
  ('footer_text',          '© 2026 DEVS_POTENCIAL. Todos os direitos reservados.'),
  ('logo_url',             ''),
  ('whatsapp_number',      '5521984158857'),
  ('whatsapp_message',     'Olá! Vim pelo site da DEVS_POTENCIAL (Rio de Janeiro). Gostaria de um orçamento, por favor. Meu bairro/Zona é: ____.'),
  ('contact_email',        'devs@devspotencial.com.br'),
  ('cta_label',            'Contratar no WhatsApp'),
  ('cta_bg_color',         '#25d366'),
  ('cta_text_color',       '#ffffff'),
  ('cta_border_color',     ''),
  ('cta_hover_bg_color',   '#1aae52'),
  ('mercadopago_checkout_url','https://link.mercadopago.com.br/devspotencial'),
  ('bank_links',           '[]'),
  ('about_text',           'Bem-vindo(a) ao site da DEVS_POTENCIAL! Aqui você encontra nossos serviços e planos disponíveis. Navegue pelos carrosséis de Serviços e Planos, escolha o que melhor atende às suas necessidades e entre em contato conosco pelo WhatsApp ou pelo formulário de contato. Todo o conteúdo é gerenciado pelo painel administrativo e atualizado em tempo real.'),
  ('servicos_hero_title',       'Serviços & Planos'),
  ('servicos_hero_subtitle',    'Soluções completas com qualidade, rapidez e garantia.'),
  ('servicos_hero_description', 'Confira abaixo nossos serviços mais solicitados e opções de planos. Para orçamento final, fale com a gente no WhatsApp.'),
  ('servicos_section_title',    'Serviços'),
  ('servicos_section_subtitle', 'Atendimentos avulsos para resolver rápido.'),
  ('planos_section_title',      'Planos & Preços'),
  ('planos_section_subtitle',   'Para manutenção recorrente e prioridade no atendimento.');

-- NOTE: if upgrading an existing database, run this once to set the Mercado Pago checkout URL:
--   UPDATE `site_settings` SET `value` = 'https://link.mercadopago.com.br/devspotencial' WHERE `key` = 'mercadopago_checkout_url' AND `value` = '';
UPDATE `site_settings`
SET    `value` = 'https://link.mercadopago.com.br/devspotencial'
WHERE  `key`   = 'mercadopago_checkout_url'
  AND  `value` = '';

-- NOTE: if upgrading an existing database, run these once to add the servicos page text settings:
INSERT IGNORE INTO `site_settings` (`key`, `value`) VALUES
  ('servicos_hero_title',       'Serviços & Planos'),
  ('servicos_hero_subtitle',    'Soluções completas com qualidade, rapidez e garantia.'),
  ('servicos_hero_description', 'Confira abaixo nossos serviços mais solicitados e opções de planos. Para orçamento final, fale com a gente no WhatsApp.'),
  ('servicos_section_title',    'Serviços'),
  ('servicos_section_subtitle', 'Atendimentos avulsos para resolver rápido.'),
  ('planos_section_title',      'Planos & Preços'),
  ('planos_section_subtitle',   'Para manutenção recorrente e prioridade no atendimento.');
