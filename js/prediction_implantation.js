document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    const idPrise = params.get("id_prise");
    const loadingEl = document.getElementById("loading-message");

    if (!idPrise) {
        loadingEl.innerHTML = "❌ Aucune borne sélectionnée.";
        return;
    }

    fetch("../php/prediction_implantation.php?id_prise=" + idPrise)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                throw new Error(res.message);
            }

            loadingEl.style.display = "none";
            document.getElementById("result-content").style.display = "block";

            document.getElementById("lat-val").innerText = res.latitude;
            document.getElementById("lon-val").innerText = res.longitude;
            document.getElementById("reel-implantation").innerText = res.implantation_reelle;
            document.getElementById("pred-implantation").innerText = res.implantation_predite;
            document.getElementById("confiance").innerText = "Confiance : " + res.confiance + " %";
            document.getElementById("nom-station").innerText = res.nom_station;
            document.getElementById("puissance").innerText = res.puissance_nominale + " kW";
            document.getElementById("nbre-pdc").innerText = res.nbre_pdc;
            document.getElementById("est-payant").innerText = res.est_payant;

            const map = L.map("map").setView([res.latitude, res.longitude], 14);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
            L.marker([res.latitude, res.longitude]).addTo(map)
                .bindPopup("<b>" + res.nom_station + "</b>")
                .openPopup();
        })
        .catch(error => {
            loadingEl.innerHTML = "❌ Erreur : " + error.message;
        });
});
