import sys
import json
import os
import joblib
import pandas as pd

try:
    if len(sys.argv) < 7:
        raise ValueError("Paramètres manquants.")

    nbre_pdc = int(sys.argv[1])
    type_prise = sys.argv[2]
    gratuit = int(sys.argv[3])
    latitude = float(sys.argv[4])
    longitude = float(sys.argv[5])
    implantation = sys.argv[6]

    current_dir = os.path.dirname(os.path.abspath(__file__))
    project_dir = os.path.dirname(current_dir)

    model = joblib.load(os.path.join(project_dir, "models", "modele_puissance.pkl"))
    encoder = joblib.load(os.path.join(project_dir, "models", "label_encoder_puissance.pkl"))
    colonnes = joblib.load(os.path.join(project_dir, "models", "colonnes_puissance.pkl"))

    data = {col: 0 for col in colonnes}

    data["nbre_pdc"] = nbre_pdc
    data["gratuit"] = gratuit
    data["consolidated_latitude"] = latitude
    data["consolidated_longitude"] = longitude

    if type_prise in data:
        data[type_prise] = 1

    implantation_col = "implantation_station_" + implantation
    if implantation_col in data:
        data[implantation_col] = 1

    X = pd.DataFrame([data], columns=colonnes)

    prediction_code = model.predict(X)[0]
    puissance = encoder.inverse_transform([prediction_code])[0]

    print(json.dumps({
        "success": True,
        "puissance": str(puissance)
    }))

except Exception as e:
    print(json.dumps({
        "success": False,
        "message": str(e)
    }))