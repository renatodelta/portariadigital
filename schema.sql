CREATE TABLE IF NOT EXISTS units (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    peer_id TEXT,
    last_seen INTEGER,
    status TEXT DEFAULT 'offline'
);

CREATE TABLE IF NOT EXISTS deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id TEXT NOT NULL,
    description TEXT NOT NULL,
    courier TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at INTEGER
);

CREATE TABLE IF NOT EXISTS visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id TEXT NOT NULL,
    visitor_name TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at INTEGER
);

CREATE TABLE IF NOT EXISTS call_signals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unit_id TEXT NOT NULL,
    status TEXT NOT NULL,
    created_at INTEGER
);

INSERT OR IGNORE INTO units (id, name) VALUES ('101', 'Casa 101 - Família Silva');
INSERT OR IGNORE INTO units (id, name) VALUES ('102', 'Casa 102 - Dr. Renato');
INSERT OR IGNORE INTO units (id, name) VALUES ('103', 'Casa 103 - Mariana & João');
INSERT OR IGNORE INTO units (id, name) VALUES ('104', 'Casa 104 - Sra. Beatriz');
INSERT OR IGNORE INTO units (id, name) VALUES ('201', 'Casa 201 - Família Costa');
INSERT OR IGNORE INTO units (id, name) VALUES ('202', 'Casa 202 - Eng. Carlos');
