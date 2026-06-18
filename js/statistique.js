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

//sert à afficher les valeurs de la puissance
async function afficherBarplotPuissance() {
  const data = await fetchPuissance();

  // Exemple : data = [{ puissance: 3, nb: 12 }, { puissance: 7, nb: 5 }]
  const labels = data.map(item => item.puissance);
  const valeurs = data.map(item => item.nb);

  const ctx = document.getElementById('puissanceChart').getContext('2d');

  // Si un graphique existe déjà, on le détruit avant d'en recréer un
  if (puissanceChart !== null) {
    puissanceChart.destroy();
  }

  puissanceChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nombre de bornes par puissance',
        data: valeurs,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

// document.getElementById('select-dep').addEventListener('change', afficherBarplotPuissance); //sert à changer le barplot quand il y a un changement de dep


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
select.addEventListener('change', () => {
    const codeDepartement = select.value;
    console.log(codeDepartement); // 75
    test(codeDepartement)

    
});



// document.addEventListener('DOMContentLoaded', async () => {
  



//   try {

//     const stations = await fetchNombreStations();
//     document.getElementById('total-stations').textContent = stations.total_stations;

//     const pointsCharge = await fetchNombrePointsCharge();
//     document.getElementById('total-points-charge').textContent = pointsCharge.total_points_charge;

//     const top = await fetchTopDepartement();
//     document.getElementById('top-departement').textContent =
//       'Dép. ' + top.departement + ' (' + top.total_points_charge + ' pts)';

//   } catch (err) {
//     console.error('Erreur lors du chargement des données :', err);
//     document.getElementById('total-stations').textContent      = 'Erreur';
//     document.getElementById('total-points-charge').textContent = 'Erreur';
//     document.getElementById('top-departement').textContent     = 'Erreur';
//   }
// });