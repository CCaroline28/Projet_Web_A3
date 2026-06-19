'use strict';

//-------------------------------------------------------PUISSANCE-----------------------------------------------------
//Renvoie un tableau de puissance (de type : [{ id_station, puissances }]) -> si plusieurs puissance disponible puissances = " X, Y"
async function fetchPuissance(dep) {
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

//Transforme le tableau de puissance en quelchose d'exploitable...
function compterStationsParPuissance(data_puissance) {
  const compteur = {};

  data_puissance.forEach(item => {
    // Séparer les puissances de chaque station (ex: "7.4, 22" -> ["7.4", "22"])
    const puissances = item.puissances.split(',').map(p => p.trim());

    //sert à calculer combien de fois chaque puissance apparaissent (Exemple de compteur un fois fini : { "22": 8, "7.4": 12, "50": 3 })
    puissances.forEach(p => {
      if (!compteur[p]) compteur[p] = 0;
      compteur[p]++;
    });
  });

  // renvoie une version plus propre de compter 
  return Object.entries(compteur) //transforme l'objet en tableau
    .sort((a, b) => parseFloat(a[0]) - parseFloat(b[0])) //convertit le nombre de puissance en int et les trie par ordre croissant 
    .map(([puissance, nb]) => ({ puissance, nb })); //ajoute des clés 
    // exemple format final : [{ puissance: "7.4", nb: 12 },{ puissance: "22", nb: 8 },{ puissance: "50", nb: 3 }]
}

//sert à créer le barplot pour la puissance
async function afficherBarplotPuissance(dep) {
  const data_puissance = await fetchPuissance(dep);
  const data = compterStationsParPuissance(data_puissance);

  const labels = data.map(item => `${item.puissance} kW`); //crée les étiquettes pour l'axe X du graphique
  const valeurs = data.map(item => item.nb); //récupère les valeurs pour l'axe Y 

  const ctx = document.getElementById('puissanceChart').getContext('2d'); //crée le barplot à cette id

  if (puissanceChart !== null) { //supprime le barplot si un existe déjà
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


//------------------------------------------------------NB de pdc------------------------------------------------------------------------

//Renvoie un tableau de nb de point de charge (de type [{ nb_pdc, total_de_pdc }])
async function fetchNbpoint(dep) {
  const response = await fetch(
    `php/request.php/statistique/nb_point_charge?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

//sert à afficher le barplot des stations par nb de point de charge
async function afficherBarplotNbpoint(dep) {
  const data_point = await fetchNbpoint(dep);

//convertit les valeurs en int et les trie par ordre croissant
  const data = data_point.sort((a, b) => parseInt(a.nb_pdc) - parseInt(b.nb_pdc));

  const labels = data.map(item => `${item.nb_pdc} pdc`); //crée les étiquettes pour l'axe X du graphique
  const valeurs = data.map(item => parseInt(item.total_de_pdc)); //récupère les valeurs pour l'axe Y 

  const ctx = document.getElementById('pointChart').getContext('2d');
  if (pointChart !== null) { //supprime le barplot si un existe déjà
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

//--------------------------------------------------------------------Type de prise ----------------------------------------------
//Renvoie un tableau de puissance (de type : [{ id_station, types_de_prise }]) -> si plusieurs type de prise disponible puissances = " X, Y"
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
    // Séparer les types de prise de chaque station (ex : "2,ef") ->  ["2","ef"]
    const types = item.types_de_prise.split(',').map(t => t.trim());

    //sert à calculer combien de fois chaque type de prise apparaissent
    types.forEach(type => {
      if (!compteur[type]) compteur[type] = 0;
      compteur[type]++;
    });
  });

    // renvoie une version plus propre de compter 
  return Object.entries(compteur) //transforme l'objet en tableau
    .map(([type, nb]) => ({ type, nb }))//ajoute des clés 
    .sort((a, b) => a.type.localeCompare(b.type));// trie par ordre alphabétique
}

//Crée le diagramme camembert
async function afficherPieType(dep) {
  const data_type = await fetchTypePrise(dep);
  const data = compterTypeStation(data_type);

  const labels = data.map(item => item.type); //crée les étiquettes pour l'axe X du graphique
  const valeurs = data.map(item => item.nb);//récupère les valeurs pour l'axe Y 

  // Génération automatique d'une couleur différente par part
  const couleurs = labels.map((_, i) => {
    const hue = (i * 360 / labels.length) % 360;
    return `hsla(${hue}, 70%, 55%, 0.7)`;
  });

  const ctx = document.getElementById('typeChart').getContext('2d'); //crée le barplot à cette id

  if (typeChart !== null) { //supprime le barplot si un existe déjà
    typeChart.destroy();
  }

  typeChart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        label: 'Répartition des types de prises',
        data: valeurs,
        backgroundColor: couleurs,
        borderColor: couleurs.map(c => c.replace('0.7', '1')),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.label}: ${ctx.parsed} station(s)`
          }
        },
        legend: {
          position: 'bottom'
        }
      }
    }
  });
}


//Renvoie un tableau d'implantation (de type : [{ implantation_station, total }])
async function fetchImplantation(dep) {

  const response = await fetch(
    `php/request.php/statistique/rep_implantation?departement=${dep}`,
    { method: 'GET' }
  );

  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

//affiche le camembert de la répartitino des stations
async function afficherPieImplantation(dep) {
  const data_implant = await fetchImplantation(dep);

  const data = data_implant.sort( //trie les valeurs par ordre croissant
    (a, b) => parseInt(a.implantation_station) - parseInt(b.implantation_station)
  );

  const labels = data.map(item => `${item.implantation_station} pdc`); //crée les étiquettes pour l'axe X du graphique
  const valeurs = data.map(item => parseInt(item.total)); //récupère les valeurs pour l'axe Y 

  const ctx = document.getElementById('implantChart').getContext('2d');//crée le barplot à cette id

  if (implantChart !== null) {//supprime le camembert si un existe déjà
    implantChart.destroy();
  }

  implantChart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        label: "Répartition des implantations",
        data: valeurs,
        backgroundColor: [
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 99, 132, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)',
          'rgba(255, 159, 64, 0.6)'
        ],
        borderColor: 'white',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.label}: ${ctx.parsed} station(s)`
          }
        },
        legend: {
          position: 'bottom'
        }
      }
    }
  });
}


//// sert à selectionner du dep choisi
const select = document.getElementById('dept');

let puissanceChart = null; // pour pouvoir  mettre à jour les diagrammes
let pointChart = null;
let typeChart = null;
let implantChart = null;



//S'il y a un changement dans le menu déroulant, on recharge tout les graphes
select.addEventListener('change', async () => {
  const codeDepartement = select.value;
  console.log(codeDepartement);
  await afficherBarplotPuissance(codeDepartement);
  await afficherBarplotNbpoint(codeDepartement);
  await afficherPieType(codeDepartement);
  await afficherPieImplantation(codeDepartement);
  console.log("tes1t");

});

//Pour charger les diagrammes à l'affichage de la page (codeDepartement = "", pour avoir les valeurs de tous les départements)
document.addEventListener('DOMContentLoaded', async () => {
  const codeDepartement = "";
  await afficherBarplotPuissance(codeDepartement);
  await afficherBarplotNbpoint(codeDepartement);
  await afficherPieType(codeDepartement);
  await afficherPieImplantation(codeDepartement);
});


