import sys
import json
import os
import joblib
import pandas as pd

try:
    if len(sys.argv) < 9:
        raise ValueError("Paramètres manquants.")

    puissance_nominale = float(sys.argv[1])
    puissance_rapide = int(sys.argv[2])
    nbre_pdc = int(sys.argv[3])
    prise_type_combo_ccs = int(sys.argv[4])
    prise_type_chademo = int(sys.argv[5])
    est_payant = int(sys.argv[6])
    latitude = float(sys.argv[7])
    longitude = float(sys.argv[8])

    current_dir = os.path.dirname(os.path.abspath(__file__))

    model = joblib.load(os.path.join(current_dir, "modele_implantation.pkl"))
    scaler = joblib.load(os.path.join(current_dir, "scaler_implantation.pkl"))
    encoder = joblib.load(os.path.join(current_dir, "label_encoder_implantation.pkl"))

    features = [
        "puissance_nominale",
        "puissance_rapide",
        "nbre_pdc",
        "prise_type_combo_ccs",
        "prise_type_chademo",
        "est_payant",
        "consolidated_latitude",
        "consolidated_longitude"
    ]

    X = pd.DataFrame([{
        "puissance_nominale": puissance_nominale,
        "puissance_rapide": puissance_rapide,
        "nbre_pdc": nbre_pdc,
        "prise_type_combo_ccs": prise_type_combo_ccs,
        "prise_type_chademo": prise_type_chademo,
        "est_payant": est_payant,
        "consolidated_latitude": latitude,
        "consolidated_longitude": longitude
    }], columns=features)

    X_scaled = scaler.transform(X)

    prediction_code = model.predict(X_scaled)[0]
    implantation = encoder.inverse_transform([prediction_code])[0]

    if hasattr(model, "predict_proba"):
        proba = model.predict_proba(X_scaled)[0]
        confiance = round(float(max(proba)) * 100, 1)
    else:
        confiance = 80.0

    print(json.dumps({
        "success": True,
        "implantation": implantation,
        "confiance": confiance
    }, ensure_ascii=False))

except Exception as e:
    print(json.dumps({
        "success": False,
        "message": str(e)
    }, ensure_ascii=False))