document.addEventListener("DOMContentLoaded", chargerPredictionPuissance);

async function chargerPredictionPuissance() {
    const params = new URLSearchParams(window.location.search);
    const idPrise = params.get("id_prise");

    if (!idPrise) {
        alert("Aucun point de charge sélectionné.");
        window.location.href = "visualisation.html";
        return;
    }

    const response = await fetch(
        "../php/prediction_puissance.php?action=predict&id_prise=" + idPrise
    );

    const data = await response.json();

    if (!data.success) {
        alert(data.message);
        return;
    }

const puissanceTexte = data.puissance_predite;
const puissance = convertirCategorieEnValeur(puissanceTexte);
    document.getElementById("puissancePredite").textContent = puissance + " kW";

   document.getElementById("legendPuissance").textContent =
    puissance + " kW";

    document.getElementById("puissanceReelle").textContent =
        puissance + " kW";

    document.getElementById("typePrise").textContent =
        data.type_prise ?? "Non renseigné";

    document.getElementById("implantation").textContent =
        data.implantation ?? "Non renseigné";

    document.getElementById("nbrePdc").textContent =
        data.nbre_pdc;

    document.getElementById("localisation").textContent =
        (data.latitude ?? "---") + ", " + (data.longitude ?? "---");

    const categorie = getCategoriePuissance(puissance);

    document.getElementById("categoriePuissance").textContent =
        categorie.label;

    document.getElementById("plagePuissance").textContent =
        categorie.plage;
}

function getCategoriePuissance(puissance) {
    if (puissance <= 22) {
        return {
            label: "Recharge standard",
            plage: "0 - 22 kW"
        };
    }

    if (puissance <= 50) {
        return {
            label: "Recharge accélérée",
            plage: "22 - 50 kW"
        };
    }

    if (puissance <= 100) {
        return {
            label: "Recharge rapide",
            plage: "50 - 100 kW"
        };
    }

    if (puissance <= 150) {
        return {
            label: "Recharge très rapide",
            plage: "100 - 150 kW"
        };
    }

    if (puissance <= 250) {
        return {
            label: "Recharge haute puissance",
            plage: "150 - 250 kW"
        };
    }

    return {
        label: "Recharge ultra-rapide",
        plage: "> 250 kW"
    };
}
function convertirCategorieEnValeur(categorie) {
    if (categorie === "Lente") return 22;
    if (categorie === "Accélérée") return 50;
    if (categorie === "Rapide") return 92;
    if (categorie === "Ultra-rapide") return 150;
    return 0;
}
