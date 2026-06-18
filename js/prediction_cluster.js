let mapCluster;
let clusterLayer;

document.addEventListener("DOMContentLoaded", async function () {
    initialiserCarteCluster();
    await chargerClusters();

    document.getElementById("btnPredict").addEventListener("click", predirePoint);
});

function initialiserCarteCluster() {
    mapCluster = L.map("mapCluster").setView([46.7, 2.5], 6);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap"
    }).addTo(mapCluster);

    clusterLayer = L.layerGroup().addTo(mapCluster);
}

function couleurCluster(cluster) {
    if (cluster == 1) return "#2563eb";
    if (cluster == 2) return "#087c2d";
    if (cluster == 3) return "#f59e0b";
    return "#7c3aed";
}

function descriptionCluster(cluster) {
    if (cluster == 1) return "Zone peu dense avec stations dispersées.";
    if (cluster == 2) return "Zone urbaine dense avec forte activité.";
    if (cluster == 3) return "Zone intermédiaire avec puissance moyenne.";
    return "Zone spécifique avec profil de recharge particulier.";
}

async function chargerClusters() {
    const response = await fetch("../php/prediction_cluster.php?action=all");
    const data = await response.json();

    clusterLayer.clearLayers();

    data.points.forEach(point => {
        const color = couleurCluster(point.cluster);

        const marker = L.circleMarker(
            [Number(point.latitude), Number(point.longitude)],
            {
                radius: 7,
                color: color,
                fillColor: color,
                fillOpacity: 0.85
            }
        );

        marker.bindPopup(`
            <strong>${point.nom_station}</strong><br>
            Cluster : ${point.cluster}<br>
            Puissance : ${point.puissance_nominale} kW<br>
            Points : ${point.nbre_pdc}<br>
            Longitude : ${Number(point.longitude).toFixed(6)}<br>
            Latitude : ${Number(point.latitude).toFixed(6)}
        `);

        clusterLayer.addLayer(marker);
    });
}

async function predirePoint() {
    const longitude = document.getElementById("longitude").value;
    const latitude = document.getElementById("latitude").value;

    if (!longitude || !latitude) {
        alert("Veuillez saisir la longitude et la latitude.");
        return;
    }

    const url =
        "../php/prediction_cluster.php?action=predict" +
        "&longitude=" + encodeURIComponent(longitude) +
        "&latitude=" + encodeURIComponent(latitude);

    const response = await fetch(url);
    const data = await response.json();

    if (!data.success) {
        alert(data.message);
        return;
    }

    const cluster = data.cluster;
    const confidence = data.confiance;

    document.getElementById("clusterResult").textContent = "Cluster " + cluster;
    document.getElementById("confidenceResult").textContent = confidence + " %";
    document.getElementById("puissanceMoyenne").textContent =
    data.puissance_moyenne + " kW";

    document.getElementById("nombrePoints").textContent = data.nombre_points;

    document.getElementById("nombreStations").textContent = data.nombre_stations;

    document.getElementById("partPoints").textContent = data.part_points + " %";
    document.getElementById("confidenceBar").style.width = confidence + "%";
    document.getElementById("descriptionResult").textContent = descriptionCluster(cluster);

    const color = couleurCluster(cluster);

    const marker = L.marker([Number(latitude), Number(longitude)])
        .addTo(mapCluster)
        .bindPopup(`
            <strong>Point sélectionné</strong><br>
            Cluster prédit : Cluster ${cluster}<br>
            Confiance : ${confidence} %<br>
            Longitude : ${Number(longitude).toFixed(6)}<br>
            Latitude : ${Number(latitude).toFixed(6)}
      `)
        .openPopup();

    mapCluster.setView([Number(latitude), Number(longitude)], 10);
}