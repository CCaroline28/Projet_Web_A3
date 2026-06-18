'use strict';
//Renvoie un tableau de puissance
async function fetchPuissance(dep) {
  // const dep = document.getElementById('select-dep').value;
  const response = await fetch(
    `php/request.php/statistique/rep_puissance?departement=${dep}`,
    { method: 'GET' }
  );

  // if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  if (!response.ok) {
    const txt = await response.text();
    console.error(txt);
    throw new Error('Erreur HTTP ' + response.status);
}
  return await response.json();
}

//Transforme le tableau en quelchose d'exploitable...
function compterStationsParPuissance(data_puissance) {
  const compteur = {};

  data_puissance.forEach(item => {
    // Séparer les puissances de chaque station (ex: "7.4, 22" → ["7.4", "22"])
    const puissances = item.puissances.split(',').map(p => p.trim());

    puissances.forEach(p => {
      if (!compteur[p]) compteur[p] = 0;
      compteur[p]++;
    });
  });

  // Trier par valeur de puissance croissante
  return Object.entries(compteur)
    .sort((a, b) => parseFloat(a[0]) - parseFloat(b[0]))
    .map(([puissance, nb]) => ({ puissance, nb }));
}

//sert à créer le barplot
async function afficherBarplotPuissance(dep) {
  // ✅ fetchPuissance est appelé ici directement
  const data_puissance = await fetchPuissance(dep);
  const data = compterStationsParPuissance(data_puissance);

  const labels = data.map(item => `${item.puissance} kW`);
  const valeurs = data.map(item => item.nb);

  const ctx = document.getElementById('puissanceChart').getContext('2d');

  if (puissanceChart !== null) {
    puissanceChart.destroy();
  }

  puissanceChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nombre de station avec cette puissance',
        data: valeurs,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.parsed.y} station(s)`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      }
    }
  });
}


async function fetchNbpoint(dep) {
  const response = await fetch(
    `php/request.php/statistique/nb_point_charge?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

async function afficherBarplotNbpoint(dep) {
  const data_point = await fetchNbpoint(dep);

  // Les données sont déjà agrégées par l'API, pas besoin de les retraiter
  const data = data_point.sort((a, b) => parseInt(a.nb_pdc) - parseInt(b.nb_pdc));

  const labels = data.map(item => `${item.nb_pdc} pdc`);
  const valeurs = data.map(item => parseInt(item.total_de_pdc));

  const ctx = document.getElementById('pointChart').getContext('2d');
  if (pointChart !== null) {
    pointChart.destroy();
  }
  pointChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nombre de stations avec ce nombre de pdc',
        data: valeurs,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.parsed.y} station(s)`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      }
    }
  });
}


async function fetchTypePrise(dep) {

  const response = await fetch(
    `php/request.php/statistique/rep_type?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}
function compterTypeStation(data_type) {
  const compteur = {};

  data_type.forEach(item => {
    // Récupérer la chaîne "2, ef" ou "combo_ccs"
    const types = item.types_de_prise.split(',').map(t => t.trim());

    types.forEach(type => {
      if (!compteur[type]) compteur[type] = 0;
      compteur[type]++;
    });
  });

  // Retourner un tableau exploitable par Chart.js
  return Object.entries(compteur)
    .map(([type, nb]) => ({ type, nb }))
    .sort((a, b) => a.type.localeCompare(b.type));
}

async function afficherBarplotType(dep) {
  const data_type = await fetchTypePrise(dep);
  const data = compterTypeStation(data_type);

  const labels = data.map(item => item.type);
  const valeurs = data.map(item => item.nb);

  const ctx = document.getElementById('typeChart').getContext('2d');

  if (typeChart !== null) {
    typeChart.destroy();
  }

  typeChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nombre de stations avec ce type de prise',
        data: valeurs,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.parsed.y} station(s)`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      }
    }
  });
}


async function fetchImplantation(dep) {

  const response = await fetch(
    `php/request.php/statistique/rep_implantation?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

async function afficherBarplotImplantation(dep) {
  const data_implant = await fetchImplantation(dep);
  console.log(data_implant);

  // Les données sont déjà agrégées par l'API, pas besoin de les retraiter
  const data = data_implant.sort((a, b) => parseInt(a.implantation_station) - parseInt(b.implantation_station));

  const labels = data.map(item => `${item.implantation_station} pdc`);
  const valeurs = data.map(item => parseInt(item.total));

  const ctx = document.getElementById('implantChart').getContext('2d');
  if (implantChart !== null) {
    implantChart.destroy();
  }
  implantChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: "Nombre de stations avec ce type d'implantation",
        data: valeurs,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.parsed.y} station(s)`
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1 }
        }
      }
    }
  });
}

//// SERT A SELECTIONNER LA Valeur du dep choisi
const select = document.getElementById('dept');

let puissanceChart = null; // pour pouvoir le mettre à jour
let pointChart = null;
let typeChart = null;
let implantChart = null;


async function test(dep) {
  const data = await fetchPuissance(dep);
  console.log(data)}

//S'il y a un changement dans le menu déroulant, on recharge tout les graphes
select.addEventListener('change', async () => {
  const codeDepartement = select.value;
  console.log(codeDepartement);
  await afficherBarplotPuissance(codeDepartement);
  await afficherBarplotNbpoint(codeDepartement);
  await afficherBarplotType(codeDepartement);
  await afficherBarplotImplantation(codeDepartement);
  console.log("test");

});

// document.addEventListener('DOMContentLoaded', async () => {
//   const valeurInitiale = '%';
//     await afficherBarplotPuissance(valeurInitiale); // Affichage au chargement ... on a rien a afficher de base...
// });


// document.addEventListener('DOMContentLoaded', async () => {
  

