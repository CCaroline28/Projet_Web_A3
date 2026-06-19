import os
import joblib
import pandas as pd
import mysql.connector

# Connexion MySQL
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="lef_irve"  # À adapter si ta base a un autre nom
)

cursor = conn.cursor(dictionary=True)

# Charger toutes les prises avec coordonnées
cursor.execute("""
    SELECT
        p.id_prise,
        s.consolidated_latitude,
        s.consolidated_longitude
    FROM prise p
    INNER JOIN station s ON p.id_station = s.id_station
    WHERE
        s.consolidated_latitude IS NOT NULL
        AND s.consolidated_longitude IS NOT NULL
""")

rows = cursor.fetchall()

# Charger le modèle KMeans
current_dir = os.path.dirname(os.path.abspath(__file__))
project_dir = os.path.dirname(current_dir)

model_path = os.path.join(project_dir, "models", "modele_kmeans.pkl")
model = joblib.load(model_path)

df = pd.DataFrame(rows)

X = df[[
    "consolidated_latitude",
    "consolidated_longitude"
]]

# Prédire les clusters
df["cluster_kmeans"] = model.predict(X)

# Mettre à jour la base
update_sql = """
    UPDATE prise
    SET cluster_kmeans = %s
    WHERE id_prise = %s
"""

for _, row in df.iterrows():
    cursor.execute(update_sql, (
        int(row["cluster_kmeans"]),
        int(row["id_prise"])
    ))

conn.commit()

print(f"{len(df)} lignes mises à jour avec les clusters KMeans.")

cursor.close()
conn.close()