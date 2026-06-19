-- ----------------------------------------------------------
-- Script MySQL corrigé pour LEF IRVE
-- Gestion correcte des accents + coordonnées GPS
-- ----------------------------------------------------------

DROP DATABASE IF EXISTS lef_irve;

CREATE DATABASE lef_irve
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE lef_irve;

-- ----------------------------
-- Table: type_paiement
-- ----------------------------

CREATE TABLE type_paiement (
    type_de_paiement VARCHAR(50) NOT NULL,

    CONSTRAINT type_paiement_PK
        PRIMARY KEY (type_de_paiement)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: Localisation
-- ----------------------------

CREATE TABLE Localisation (
    consolidated_code_postal INT NOT NULL,
    consolidated_commune VARCHAR(50) NOT NULL,

    CONSTRAINT Localisation_PK
        PRIMARY KEY (consolidated_code_postal)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: station
-- ----------------------------

CREATE TABLE station (
    implantation_station VARCHAR(50) NOT NULL,

    CONSTRAINT station_PK
        PRIMARY KEY (implantation_station)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: type_de_prise
-- ----------------------------

CREATE TABLE type_de_prise (
    type_de_prise VARCHAR(50) NOT NULL,

    CONSTRAINT type_de_prise_PK
        PRIMARY KEY (type_de_prise)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: prise
-- ----------------------------

CREATE TABLE prise (
    id_prise INT NOT NULL AUTO_INCREMENT,
    nbre_pdc INT NOT NULL,
    puissance_nominale FLOAT NOT NULL,
    condition_acces VARCHAR(50) NOT NULL,
    reservation TINYINT(1) NOT NULL,
    date_mise_en_service DATETIME NOT NULL,

    -- IMPORTANT :
    -- Les coordonnées ne doivent pas être en INT.
    -- Sinon tu perds les décimales nécessaires pour Leaflet.
    consolidated_longitude DECIMAL(10,7) NOT NULL,
    consolidated_latitude DECIMAL(10,7) NOT NULL,

    implantation_station VARCHAR(50) NOT NULL,
    consolidated_code_postal INT NOT NULL,

    CONSTRAINT prise_PK
        PRIMARY KEY (id_prise),

    CONSTRAINT prise_implantation_station_FK
        FOREIGN KEY (implantation_station)
        REFERENCES station (implantation_station),

    CONSTRAINT prise_consolidated_code_postal_FK
        FOREIGN KEY (consolidated_code_postal)
        REFERENCES Localisation (consolidated_code_postal)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
ALTER TABLE prise ADD cluster_kmeans INT NULL;
-- ----------------------------
-- Table: de_type
-- ----------------------------

CREATE TABLE de_type (
    type_de_prise VARCHAR(50) NOT NULL,
    id_prise INT NOT NULL,

    CONSTRAINT de_type_PK
        PRIMARY KEY (type_de_prise, id_prise),

    CONSTRAINT de_type_type_de_prise_FK
        FOREIGN KEY (type_de_prise)
        REFERENCES type_de_prise (type_de_prise),

    CONSTRAINT de_type_id_prise_FK
        FOREIGN KEY (id_prise)
        REFERENCES prise (id_prise)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: paye_avec
-- ----------------------------

CREATE TABLE paye_avec (
    type_de_paiement VARCHAR(50) NOT NULL,
    id_prise INT NOT NULL,

    CONSTRAINT paye_avec_PK
        PRIMARY KEY (type_de_paiement, id_prise),

    CONSTRAINT paye_avec_type_de_paiement_FK
        FOREIGN KEY (type_de_paiement)
        REFERENCES type_paiement (type_de_paiement),

    CONSTRAINT paye_avec_id_prise_FK
        FOREIGN KEY (id_prise)
        REFERENCES prise (id_prise)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;