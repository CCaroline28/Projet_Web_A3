import pandas as pd
import mysql.connector
from datetime import datetime

# ==============================
# À MODIFIER SI BESOIN
# ==============================

CSV_PATH = "../data/export_IA.csv"

DB_HOST = "localhost"
DB_NAME = "lef_irve"
DB_USER = "root"
DB_PASSWORD = ""

# ==============================
# Connexion MySQL
# ==============================

connexion = mysql.connector.connect(
    host=DB_HOST,
    database=DB_NAME,
    user=DB_USER,
    password=DB_PASSWORD,
    charset="utf8mb4"
)

cursor = connexion.cursor()

# ==============================
# Lecture du CSV
# ==============================

df = pd.read_csv(CSV_PATH)

# On garde seulement les lignes avec code postal et commune
df = df.dropna(subset=[
    "consolidated_code_postal",
    "consolidated_commune",
    "nom_station",
    "implantation_station"
])

# ==============================
# Fonctions utiles
# ==============================

def clean_text(value, default="Non renseigné"):
    if pd.isna(value):
        return default
    return str(value).strip()[:50]


def clean_bool(value):
    if pd.isna(value):
        return 0
    if value in [True, "True", "true", 1, "1", "Oui", "oui"]:
        return 1
    return 0


def clean_date(value):
    if pd.isna(value):
        return "2000-01-01 00:00:00"

    value = str(value)

    try:
        return datetime.strptime(value, "%Y-%m-%d").strftime("%Y-%m-%d %H:%M:%S")
    except ValueError:
        return "2000-01-01 00:00:00"


# ==============================
# Insertion types fixes
# ==============================

types_prises = ["Type EF", "Type 2", "Combo CCS", "CHAdeMO", "Autre"]
types_paiement = ["Gratuit", "Paiement à l'acte", "Carte bancaire", "Autre"]

for type_prise in types_prises:
    cursor.execute("""
        INSERT IGNORE INTO type_de_prise(type_de_prise)
        VALUES (%s)
    """, (type_prise,))

for type_paiement in types_paiement:
    cursor.execute("""
        INSERT IGNORE INTO type_paiement(type_de_paiement)
        VALUES (%s)
    """, (type_paiement,))

connexion.commit()

# ==============================
# Import des données
# ==============================

id_station = 1
id_prise = 1

stations_deja_creees = {}

for _, row in df.iterrows():

    code_postal = int(row["consolidated_code_postal"])
    commune = clean_text(row["consolidated_commune"])
    nom_station = clean_text(row["nom_station"])
    implantation = clean_text(row["implantation_station"])

    latitude = float(row["consolidated_latitude"])
    longitude = float(row["consolidated_longitude"])

    # ------------------------------
    # Localisation
    # ------------------------------

    cursor.execute("""
        INSERT IGNORE INTO Localisation(
            consolidated_code_postal,
            consolidated_commune
        )
        VALUES (%s, %s)
    """, (code_postal, commune))

    # ------------------------------
    # Station
    # ------------------------------

    station_key = nom_station + "_" + str(code_postal)

    if station_key not in stations_deja_creees:

        stations_deja_creees[station_key] = id_station

        cursor.execute("""
            INSERT INTO station(
                id_station,
                implantation_station,
                nom_station,
                consolidated_latitude,
                consolidated_longitude
            )
            VALUES (%s, %s, %s, %s, %s)
        """, (
            id_station,
            implantation,
            nom_station,
            latitude,
            longitude
        ))

        current_id_station = id_station
        id_station += 1

    else:
        current_id_station = stations_deja_creees[station_key]

    # ------------------------------
    # Prise
    # ------------------------------

    cursor.execute("""
        INSERT INTO prise(
            id_prise,
            nbre_pdc,
            puissance_nominale,
            condition_acces,
            reservation,
            date_mise_en_service,
            id_station,
            consolidated_code_postal
        )
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    """, (
        id_prise,
        int(row["nbre_pdc"]),
        float(row["puissance_nominale"]),
        clean_text(row["condition_acces"]),
        clean_bool(row["reservation"]),
        clean_date(row["date_mise_en_service"]),
        current_id_station,
        code_postal
    ))

    # ------------------------------
    # Types de prise
    # ------------------------------

    correspondance_prises = {
        "prise_type_ef": "Type EF",
        "prise_type_2": "Type 2",
        "prise_type_combo_ccs": "Combo CCS",
        "prise_type_chademo": "CHAdeMO",
        "prise_type_autre": "Autre"
    }

    for colonne, type_prise in correspondance_prises.items():
        if clean_bool(row[colonne]) == 1:
            cursor.execute("""
                INSERT IGNORE INTO de_type(
                    type_de_prise,
                    id_prise
                )
                VALUES (%s, %s)
            """, (type_prise, id_prise))

    # ------------------------------
    # Types de paiement
    # ------------------------------

    correspondance_paiement = {
        "gratuit": "Gratuit",
        "paiement_acte": "Paiement à l'acte",
        "paiement_cb": "Carte bancaire",
        "paiement_autre": "Autre"
    }

    for colonne, type_paiement in correspondance_paiement.items():
        if clean_bool(row[colonne]) == 1:
            cursor.execute("""
                INSERT IGNORE INTO paye_avec(
                    type_de_paiement,
                    id_prise
                )
                VALUES (%s, %s)
            """, (type_paiement, id_prise))

    id_prise += 1

connexion.commit()

cursor.close()
connexion.close()

print("Import terminé avec succès.")
print(f"{id_station - 1} stations importées.")
print(f"{id_prise - 1} prises importées.")