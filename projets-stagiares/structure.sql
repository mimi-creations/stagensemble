CREATE DATABASE IF NOT EXISTS espace_stagiaire;
USE espace_stagiaire;
 
CREATE TABLE IF NOT EXISTS anciens_stagiaires (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(50)  NOT NULL,
    prenom       VARCHAR(50)  NOT NULL,
    email        VARCHAR(100) NOT NULL,
    ecole        VARCHAR(100),
    annee_stage  VARCHAR(4),
    duree_stage  VARCHAR(30)
);
 
CREATE TABLE IF NOT EXISTS chat (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pseudo      VARCHAR(50)  NOT NULL,
    message     TEXT         NOT NULL,
    date_envoi  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
 
CREATE TABLE IF NOT EXISTS ressources (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    titre               VARCHAR(100) NOT NULL,
    sujet               TEXT         NOT NULL,
    probleme_rencontre  TEXT,
    solution_utilisee   TEXT,
    auteur              VARCHAR(50),
    date_publication    DATE
);
 
INSERT INTO anciens_stagiaires (nom, prenom, email, ecole, annee_stage, duree_stage)
VALUES ('Doe', 'John', 'j.doe@awb.ma', 'Université de Casablanca', '2023', '3 mois');
