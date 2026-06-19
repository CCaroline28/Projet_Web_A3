USE lef_irve;

INSERT INTO type_paiement VALUES
('Carte bancaire'),
('Application mobile'),
('Paiement sans contact'),
('Gratuit');

INSERT INTO Localisation VALUES
(75001, 'Paris'),
(29200, 'Brest'),
(44000, 'Nantes'),
(14000, 'Caen');

INSERT INTO station VALUES
('Voirie'),
('Parking public'),
('Centre commercial'),
('Station-service');

INSERT INTO type_de_prise VALUES
('Type 2'),
('Combo CCS'),
('CHAdeMO'),
('E/F');

INSERT INTO prise (
    nbre_pdc,
    puissance_nominale,
    condition_acces,
    reservation,
    date_mise_en_service,
    consolidated_longitude,
    consolidated_latitude,
    implantation_station,
    consolidated_code_postal
)
VALUES
(4, 22.0, 'Accès libre', 0, '2024-01-15 00:00:00', 2.3522000, 48.8566000, 'Voirie', 75001),
(6, 50.0, 'Accès avec badge', 1, '2024-03-10 00:00:00', -4.4861000, 48.3904000, 'Parking public', 29200),
(8, 150.0, 'Accès libre', 1, '2024-05-20 00:00:00', -1.5536000, 47.2184000, 'Centre commercial', 44000),
(3, 22.0, 'Accès réservé', 0, '2024-06-12 00:00:00', -0.3707000, 49.1829000, 'Station-service', 14000);

INSERT INTO de_type VALUES
('Type 2', 1),
('Combo CCS', 2),
('Combo CCS', 3),
('CHAdeMO', 3),
('E/F', 4);

INSERT INTO paye_avec VALUES
('Carte bancaire', 1),
('Application mobile', 2),
('Paiement sans contact', 3),
('Gratuit', 4);
