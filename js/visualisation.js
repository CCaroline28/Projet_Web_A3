'use strict';

// Variables globales pour mémoriser l'état courant
let currentDep = '';
let currentTypes = [];
let currentLimit = 50;

async function chargerTableau(dep, typesPrise, page = 1, limit = currentLimit) {

  // Mémoriser les filtres actifs
  currentDep   = dep;
  currentTypes = typesPrise;
  currentLimit = limit;

  var message = document.getElementById('table-message');
  var tableau = document.getElementById('tableau-prises');
  var tbody   = document.getElementById('tbody-prises');

  message.textContent = 'Chargement...';
  message.style.display = 'block';
  tableau.style.display = 'none';

  var params = new URLSearchParams();

  if (dep) params.append('dep', dep);
  if (typesPrise.length) params.append('types', typesPrise.join(','));

  params.append('page', page);
  params.append('limit', limit);

  try {

    var response = await fetch('php/request.php/visu/prises?' + params.toString());

    if (!response.ok) throw new Error('Erreur HTTP ' + response.status);

    var json = await response.json();

    var data  = json.data;
    var total = json.total;

    if (!data || !data.length) {
      message.textContent = 'Aucun résultat trouvé.';
      return;
    }

    // Pagination
    renderPagination(total, limit, page);

    // Rendu table
    var html = '';

    data.forEach(function(row) {

      var types = row.type_de_prise
        ? row.type_de_prise.split('-').map(t => '<span class="badge">' + t + '</span>').join('')
        : '-';

      var paiements = row.type_de_paiement
        ? row.type_de_paiement.split('-').map(p => '<span class="badge">' + p + '</span>').join('')
        : '-';

      html +=
        '<tr>' +
          '<td>' + (row.nom_station             || '-') + '</td>' +
          '<td>' + (row.consolidated_commune     || '-') + '</td>' +
          '<td>' + (row.consolidated_code_postal || '-') + '</td>' +
          '<td>' + (row.implantation_station     || '-') + '</td>' +
          '<td>' + (row.nbre_pdc                 || '-') + '</td>' +
          '<td>' + (row.puissance_nominale       || '-') + '</td>' +
          '<td>' + types                                 + '</td>' +
          '<td>' + paiements                            + '</td>' +
          '<td>' + (row.reservation == 1 ? 'Oui' : 'Non') + '</td>' +
          '<td>' + (row.condition_acces          || '-') + '</td>' +
        '</tr>';
    });

    tbody.innerHTML = html;

    message.style.display = 'none';
    tableau.style.display = 'table';

  } catch (e) {
    message.textContent = 'Erreur lors du chargement des données.';
    console.error(e);
  }
}

function renderPagination(total, limit, currentPage) {
  var container = document.getElementById('pagination');
  container.innerHTML = '';

  var pages = Math.ceil(total / limit);
  if (pages <= 1) return;

  for (let i = 1; i <= pages; i++) {
    var btn = document.createElement('button');
    btn.textContent = i;
    if (i === currentPage) btn.disabled = true; // page active
    btn.addEventListener('click', function() {
      chargerTableau(currentDep, currentTypes, i, limit);
    });
    container.appendChild(btn);
  }
}

document.addEventListener('DOMContentLoaded', function() {

  chargerTableau('', [], 1, currentLimit);

  document.getElementById('btn-filtrer').addEventListener('click', function() {
    var dep    = document.getElementById('filtre-dep').value.trim();
    var cases  = document.querySelectorAll('.checkbox-list input:checked');
    var typesPrise = Array.from(cases).map(function(c) { return c.value; });
    chargerTableau(dep, typesPrise, 1, currentLimit);
  });

  document.getElementById('itemsPerPage').addEventListener('change', function() {
    chargerTableau(currentDep, currentTypes, 1, parseInt(this.value));
  });

});