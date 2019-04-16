CREATE DATABASE IF NOT EXISTS ivote DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE ivote;

CREATE TABLE IF NOT EXISTS users (
    uuid CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    sent_credentials BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (uuid)
);

CREATE TABLE IF NOT EXISTS votesessions (
    uuid CHAR(36) NOT NULL,
    name VARCHAR(250) NOT NULL,
    open BOOLEAN NOT NULL DEFAULT FALSE,
    choices VARCHAR(16000) NOT NULL DEFAULT 'a:0:{}',
    PRIMARY KEY (uuid),
    UNIQUE INDEX unique_name (name(250))
);

CREATE TABLE IF NOT EXISTS votes (
    uuid CHAR(36) NOT NULL,
    votesession_uuid CHAR(36) NOT NULL,
    user_uuid CHAR(36) NOT NULL,
    value INT NOT NULL,
    PRIMARY KEY (uuid),
    UNIQUE INDEX single_vote (votesession_uuid, user_uuid)
);