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
        label: 'Nombre de station avec cette puissance puissance',
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


async function fetchNbpoint() {
  const dep = document.getElementById('select-dep').value;

  const response = await fetch(
    `php/request.php/statistique/nb_point_charge?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}


async function fetchTypePrise() {
  const dep = document.getElementById('select-dep').value;

  const response = await fetch(
    `php/request.php/statistique/rep_type?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

async function fetchImplantation() {
  const dep = document.getElementById('select-dep').value;

  const response = await fetch(
    `php/request.php/statistique/rep_implantation?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

//// SERT A SELECTIONNER LA Valeur du dep choisi
const select = document.getElementById('dept');

let puissanceChart = null; // pour pouvoir le mettre à jour


async function test(dep) {
  const data = await fetchPuissance(dep);
  console.log(data)}

//S'il y a un changement dans le menu déroulant, on recharge tout les graphes
select.addEventListener('change', async () => {
  const codeDepartement = select.value;
  console.log(codeDepartement);
  await afficherBarplotPuissance(codeDepartement);
});

// document.addEventListener('DOMContentLoaded', async () => {
//   const valeurInitiale = select.value;
//   if (valeurInitiale) {
//     await afficherBarplotPuissance(valeurInitiale); // Affichage au chargement ... on a rien a afficher de base...
//   }
// });


// document.addEventListener('DOMContentLoaded', async () => {
  

