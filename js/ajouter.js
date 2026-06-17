document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAjouterInstallation');
    const messageDiv = document.getElementById('message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = {};

        // Récupération intelligente des données
        formData.forEach((value, key) => {
            if (key === 'paiement' || key === 'prise') {
                if (!data[key]) data[key] = [];
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });

        // Conversion des tableaux pour l'envoi
        data.paiement = data.paiement ? data.paiement.join(', ') : '';
        data.prise = data.prise ? data.prise.join(', ') : '';

        try {
            const response = await fetch('php/request.php?route=installation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            });

            const result = await response.json();

            if (result.success) {
                messageDiv.innerHTML = "<p style='color:green'>Succès : Station enregistrée.</p>";
                form.reset();
            } else {
                throw new Error(result.error || "Erreur inconnue");
            }
        } catch (err) {
            messageDiv.innerHTML = "<p style='color:red'>Erreur : " + err.message + "</p>";
        }
    });
});