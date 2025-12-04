-- =============================================
-- Base de données pour le projet Events Management
-- =============================================

DROP DATABASE IF EXISTS events_management;
CREATE DATABASE events_management DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE events_management;

-- =============================================
-- Table: utilisateurs
-- =============================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Table: evenements
-- =============================================
CREATE TABLE evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('normal', 'quiz') NOT NULL DEFAULT 'normal',
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    image_url VARCHAR(500) DEFAULT 'https://via.placeholder.com/600x400?text=No+Image',
    nombre_questions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Table: questions (Jointure avec evenements)
-- =============================================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    texte_question TEXT NOT NULL,
    ordre INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: reponses (Jointure avec questions)
-- =============================================
CREATE TABLE reponses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    texte_reponse VARCHAR(500) NOT NULL,
    est_correcte BOOLEAN DEFAULT FALSE,
    ordre INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: participations (Jointure avec utilisateurs et evenements)
-- =============================================
CREATE TABLE participations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    evenement_id INT NOT NULL,
    commentaire TEXT,
    fichier_url VARCHAR(500),
    statut ENUM('en_attente', 'approuve', 'rejete') DEFAULT 'en_attente',
    date_participation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: resultats_quiz
-- =============================================
CREATE TABLE resultats_quiz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    evenement_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    pourcentage DECIMAL(5,2) NOT NULL,
    date_passage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (evenement_id) REFERENCES evenements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: reponses_utilisateur
-- =============================================
CREATE TABLE reponses_utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resultat_id INT NOT NULL,
    question_id INT NOT NULL,
    reponse_id INT NOT NULL,
    est_correcte BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (resultat_id) REFERENCES resultats_quiz(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (reponse_id) REFERENCES reponses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Données de test
-- =============================================

-- Utilisateurs
INSERT INTO utilisateurs (nom, prenom, email) VALUES
('Dupont', 'Jean', 'jean.dupont@email.com'),
('Martin', 'Marie', 'marie.martin@email.com'),
('Bernard', 'Pierre', 'pierre.bernard@email.com');

-- Événements normaux
INSERT INTO evenements (type, titre, description, date_debut, date_fin, image_url) VALUES
('normal', 'Conférence Intelligence Artificielle', 'Découvrez les dernières avancées en IA et machine learning avec des experts du domaine.', '2025-02-01 09:00:00', '2025-02-01 18:00:00', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=600'),
('normal', 'Hackathon Innovation', 'Participez à notre hackathon annuel et développez des solutions innovantes en 48 heures.', '2025-02-15 08:00:00', '2025-02-17 20:00:00', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=600');

-- Événements Quiz
INSERT INTO evenements (type, titre, description, date_debut, date_fin, image_url, nombre_questions) VALUES
('quiz', 'Quiz Programmation Web', 'Testez vos connaissances en développement web (HTML, CSS, JavaScript, PHP).', '2025-01-20 10:00:00', '2025-01-30 23:59:00', 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=600', 3);

-- Questions pour le quiz
INSERT INTO questions (evenement_id, texte_question, ordre) VALUES
(3, 'Quel langage est utilisé pour styliser les pages web ?', 1),
(3, 'Quelle balise HTML est utilisée pour créer un lien hypertexte ?', 2),
(3, 'Quel est le résultat de 2 + "2" en JavaScript ?', 3);

-- Réponses pour les questions
-- Question 1
INSERT INTO reponses (question_id, texte_reponse, est_correcte, ordre) VALUES
(1, 'HTML', FALSE, 1),
(1, 'CSS', TRUE, 2),
(1, 'JavaScript', FALSE, 3),
(1, 'PHP', FALSE, 4);

-- Question 2
INSERT INTO reponses (question_id, texte_reponse, est_correcte, ordre) VALUES
(2, '<link>', FALSE, 1),
(2, '<a>', TRUE, 2),
(2, '<href>', FALSE, 3),
(2, '<url>', FALSE, 4);

-- Question 3
INSERT INTO reponses (question_id, texte_reponse, est_correcte, ordre) VALUES
(3, '4', FALSE, 1),
(3, '"22"', TRUE, 2),
(3, 'NaN', FALSE, 3),
(3, 'Erreur', FALSE, 4);
