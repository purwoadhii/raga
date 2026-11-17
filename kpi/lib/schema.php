<?php
/**
 * Definisi tabel database aplikasi KPI.
 * Dipakai oleh install.php dan oleh skrip pengujian.
 */

function kpi_schema_statements(): array
{
    $s = [];

    $s[] = "CREATE TABLE settings (
        skey   VARCHAR(64) NOT NULL PRIMARY KEY,
        svalue TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE users (
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(120) NOT NULL,
        title         VARCHAR(120) NOT NULL DEFAULT '',
        level         VARCHAR(10)  NOT NULL,
        parent_id     INT UNSIGNED NULL,
        dept          VARCHAR(120) NOT NULL DEFAULT '',
        username      VARCHAR(64)  NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        is_active     TINYINT NOT NULL DEFAULT 1,
        created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ux_users_username (username),
        KEY ix_users_parent (parent_id),
        CONSTRAINT fk_users_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE kpis (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id   INT UNSIGNED NOT NULL,
        name       VARCHAR(160) NOT NULL,
        persp      VARCHAR(10)  NOT NULL DEFAULT 'fin',
        unit       VARCHAR(40)  NOT NULL DEFAULT '',
        target     DECIMAL(18,4) NOT NULL DEFAULT 0,
        polarity   VARCHAR(4) NOT NULL DEFAULT 'max',
        weight     DECIMAL(6,2) NOT NULL DEFAULT 0,
        parent_id  INT UNSIGNED NULL,
        rollup     VARCHAR(8) NOT NULL DEFAULT 'manual',
        descr      TEXT,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY ix_kpis_owner (owner_id),
        KEY ix_kpis_parent (parent_id),
        CONSTRAINT fk_kpis_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE,
        CONSTRAINT fk_kpis_parent FOREIGN KEY (parent_id) REFERENCES kpis (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE actuals (
        id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kpi_id       INT UNSIGNED NOT NULL,
        yr           SMALLINT NOT NULL,
        mo           TINYINT NOT NULL,
        val          DECIMAL(18,4) NULL,
        status       VARCHAR(12) NOT NULL DEFAULT 'draft',
        note         VARCHAR(500) NOT NULL DEFAULT '',
        submitted_by INT UNSIGNED NULL,
        approved_by  INT UNSIGNED NULL,
        updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY ux_actuals_period (kpi_id, yr, mo),
        CONSTRAINT fk_actuals_kpi FOREIGN KEY (kpi_id) REFERENCES kpis (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE login_attempts (
        ident        VARCHAR(190) NOT NULL PRIMARY KEY,
        attempts     INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL DEFAULT NULL,
        updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE activity (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED NULL,
        action     VARCHAR(40) NOT NULL,
        detail     VARCHAR(500) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY ix_activity_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $s;
}

/** Urutan hapus tabel (anak lebih dulu). */
function kpi_schema_tables(): array
{
    return ['activity', 'login_attempts', 'actuals', 'kpis', 'users', 'settings'];
}
