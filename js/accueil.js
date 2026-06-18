document.addEventListener("DOMContentLoaded", chargerStatistiquesAccueil);

async function chargerStatistiquesAccueil() {
    try {
        const response = await fetch("../php/accueil_stats.php"); 
        const data = await response.json();

        document.getElementById("nbStations").textContent =
            Number(data.nbStations).toLocaleString("fr-FR");

        document.getElementById("nbPrises").textContent =
            Number(data.nbPdc).toLocaleString("fr-FR");

        document.getElementById("topDepartement").textContent =
            data.departementTop;

        document.getElementById("nbDepartement").textContent =
            data.nbDepartementTop + " stations";

    } catch (error) {
        console.error("Erreur AJAX :", error);
    }
}