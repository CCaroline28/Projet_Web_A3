'use strict';

async function fetchNombreStations() {
  const response = await fetch('php/request.php/stats/stations', { method: 'GET' });
  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

async function fetchNombrePointsCharge() {
  const response = await fetch('php/request.php/stats/points-charge', { method: 'GET' });
  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

async function fetchTopDepartement() {
  const response = await fetch('php/request.php/stats/top-departement', { method: 'GET' });
  if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
  return await response.json();
}

document.addEventListener('DOMContentLoaded', async () => {
  try {

    const stations = await fetchNombreStations();
    document.getElementById('total-stations').textContent = stations.total_stations;

    const pointsCharge = await fetchNombrePointsCharge();
    document.getElementById('total-points-charge').textContent = pointsCharge.total_points_charge;

    const top = await fetchTopDepartement();
    document.getElementById('top-departement').textContent =
      'Dép. ' + top.departement + ' (' + top.total_points_charge + ' pts)';

  } catch (err) {
    console.error('Erreur lors du chargement des données :', err);
    document.getElementById('total-stations').textContent      = 'Erreur';
    document.getElementById('total-points-charge').textContent = 'Erreur';
    document.getElementById('top-departement').textContent     = 'Erreur';
  }
});