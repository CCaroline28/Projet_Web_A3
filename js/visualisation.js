let map;
let markersLayer;
let ligneSelectionnee = null;

document.addEventListener("DOMContentLoaded", async function () {
    initialiserCarte();
    await chargerFiltres();
    await chargerDonnees();
document.getElementById("btnTableau").addEventListener("click", function(){
    document.getElementById("sectionTableau").classList.remove("hidden");
    document.getElementById("sectionCarte").classList.add("hidden");

    this.classList.add("active");
    document.getElementById("btnCarte").classList.remove("active");
});

document.getElementById("btnCarte").addEventListener("click", function(){
    document.getElementById("sectionCarte").classList.remove("hidden");
    document.getElementById("sectionTableau").classList.add("hidden");

    this.classList.add("active");
    document.getElementById("btnTableau").classList.remove("active");

    setTimeout(() => {
        map.invalidateSize();
    }, 200);
});
    document.getElementById("btnValider").addEventListener("click", chargerDonnees);

    document.addEventListener("click", function () {
        document.getElementById("contextMenu").style.display = "none";
    });

    document.getElementById("modifierBtn").addEventListener("click", function () {
        if (ligneSelectionnee) {
            window.location.href = "modification.html?id_prise=" + ligneSelectionnee;
        }
    });

    document.getElementById("supprimerBtn").addEventListener("click", async function () {
        if (!ligneSelectionnee) return;

        if (!confirm("Voulez-vous vraiment supprimer ce point de charge ?")) return;

        const response = await fetch("../php/supprimer_prise.php?id_prise=" + ligneSelectionnee);
        const result = await response.json();

        if (result.success) {
            alert("Point de charge supprimé.");
            await chargerDonnees();
        } else {
            alert(result.message);
        }
    });
});

function initialiserCarte() {
    map = L.map("map").setView([46.7, 2.5], 6);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap"
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
}

async function chargerFiltres() {
    const response = await fetch("../php/visualisation_data.php?action=filtres");
    const data = await response.json();

    const departementSelect = document.getElementById("departement");
    const typeSelect = document.getElementById("typePrise");

    data.departements.forEach(dep => {
        const option = document.createElement("option");
        option.value = dep.code;
        option.textContent = dep.nom;
        departementSelect.appendChild(option);
    });

    data.types.forEach(type => {
        const option = document.createElement("option");
        option.value = type;
        option.textContent = type;
        typeSelect.appendChild(option);
    });
}

async function chargerDonnees() {
    const departement = document.getElementById("departement").value;
    const typePrise = document.getElementById("typePrise").value;

    const url =
        "../php/visualisation_data.php?action=points" +
        "&departement=" + encodeURIComponent(departement) +
        "&type=" + encodeURIComponent(typePrise);

    const response = await fetch(url);
    const data = await response.json();

    const tbody = document.getElementById("tableBody");
    tbody.innerHTML = "";
    markersLayer.clearLayers();

    document.getElementById("nbResultats").textContent =
        "Nombre de résultats : " + data.total;

    data.points.forEach(point => {
        const tr = document.createElement("tr");

       tr.innerHTML = `
    <td>${point.id_prise}</td>
    <td>${point.nom_station}</td>
    <td>${point.longitude}</td>
    <td>${point.latitude}</td>
    <td>${point.departement}</td>
    <td>${point.code_postal}</td>
    <td>${point.type_paiement ?? "Non renseigné"}</td>
    <td><span class="badge">${point.type_de_prise ?? "Non renseigné"}</span></td>
    <td>${point.nbre_pdc}</td>
    <td>${point.puissance_nominale}</td>
    <td>${point.implantation_station}</td>
    <td>${point.reservation == 1 ? "Oui" : "Non"}</td>
    <td>${point.condition_acces}</td>
    <td>${point.date_mise_en_service}</td>
`;
        tr.addEventListener("contextmenu", function (event) {
            event.preventDefault();

            ligneSelectionnee = point.id_prise;

            const menu = document.getElementById("contextMenu");
            menu.style.display = "block";
            menu.style.left = event.pageX + "px";
            menu.style.top = event.pageY + "px";
        });

        tbody.appendChild(tr);

        if (point.latitude && point.longitude) {
            const marker = L.marker([
                Number(point.latitude),
                Number(point.longitude)
            ]);

            marker.bindPopup(`
                <strong>${point.nom_station}</strong><br>
                Département : ${point.departement}<br>
                Type : ${point.type_de_prise ?? "Non renseigné"}<br>
                Puissance : ${point.puissance_nominale} kW<br>
                Points : ${point.nbre_pdc}
            `);

            markersLayer.addLayer(marker);
        }
    });
}