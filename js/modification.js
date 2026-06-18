document.addEventListener("DOMContentLoaded", async function () {
    const params = new URLSearchParams(window.location.search);
    const idPrise = params.get("id_prise");

    if (!idPrise) {
        alert("Aucun point sélectionné.");
        window.location.href = "visualisation.html";
        return;
    }

    await chargerPrise(idPrise);

    document
        .getElementById("formModification")
        .addEventListener("submit", modifierPrise);
});

async function chargerPrise(idPrise) {
    const response = await fetch("../php/get_prise.php?id_prise=" + idPrise);
    const data = await response.json();

    if (!data.success) {
        alert(data.message);
        return;
    }

    const prise = data.prise;

    document.getElementById("id_prise").value = prise.id_prise;

    document.getElementById("nom_station").value = prise.nom_station;
    document.getElementById("longitude").value = prise.consolidated_longitude;
    document.getElementById("latitude").value = prise.consolidated_latitude;
    document.getElementById("code_postal").value = prise.consolidated_code_postal;
    document.getElementById("implantation_station").value = prise.implantation_station;

    document.getElementById("nbre_pdc").value = prise.nbre_pdc;
    document.getElementById("puissance_nominale").value = prise.puissance_nominale;
    document.getElementById("condition_acces").value = prise.condition_acces;
    document.getElementById("reservation").value = prise.reservation;
    document.getElementById("date_mise_en_service").value = prise.date_mise_en_service;
}

async function modifierPrise(event) {
    event.preventDefault();

    const formData = new FormData();

    formData.append("id_prise", document.getElementById("id_prise").value);

    formData.append("nom_station", document.getElementById("nom_station").value);
    formData.append("longitude", document.getElementById("longitude").value);
    formData.append("latitude", document.getElementById("latitude").value);
    formData.append("code_postal", document.getElementById("code_postal").value);
    formData.append("implantation_station", document.getElementById("implantation_station").value);

    formData.append("nbre_pdc", document.getElementById("nbre_pdc").value);
    formData.append("puissance_nominale", document.getElementById("puissance_nominale").value);
    formData.append("condition_acces", document.getElementById("condition_acces").value);
    formData.append("reservation", document.getElementById("reservation").value);
    formData.append("date_mise_en_service", document.getElementById("date_mise_en_service").value);

    const response = await fetch("../php/modifier_prise.php", {
        method: "POST",
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        alert("Modification enregistrée.");
        window.location.href = "visualisation.html";
    } else {
        alert(result.message);
    }
}