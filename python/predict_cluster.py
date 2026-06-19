import sys
import json
import os
import joblib
import pandas as pd

try:
    if len(sys.argv) < 3:
        raise ValueError("Longitude et latitude manquantes.")

    longitude = float(sys.argv[1])
    latitude = float(sys.argv[2])

    current_dir = os.path.dirname(os.path.abspath(__file__))
    project_dir = os.path.dirname(current_dir)

    model_path = os.path.join(
        project_dir,
        "models",
        "modele_kmeans.pkl"
    )

    model = joblib.load(model_path)

    X = pd.DataFrame([{
        "consolidated_latitude": latitude,
        "consolidated_longitude": longitude
    }])

    cluster = int(model.predict(X)[0])

    print(json.dumps({
        "success": True,
        "cluster": cluster
    }))

except Exception as e:
    print(json.dumps({
        "success": False,
        "message": str(e)
    }))