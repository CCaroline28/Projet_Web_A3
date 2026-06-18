document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formAjouterInstallation');
    const messageDiv = document.getElementById('message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries()); //récupère les informations du formulaire en association clé/valeurs

        // Récupérer les checkbox séparément (pour qu'on récupère bien plusieurs valeurs si plusieurs cases sont cochés) 
        data.paiement = formData.getAll('paiement');
        data.prise = formData.getAll('prise');

        try {
            const response = await fetch('php/request.php/ajouter', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                messageDiv.innerHTML = "<p style='color:green'>Succès : Station et prise ajoutées.</p>";
                form.reset();
            } else {
                throw new Error(result.error || "Erreur lors de l'enregistrement");
            }
        } catch (err) {
            messageDiv.innerHTML = "<p style='color:red'>Erreur : " + err.message + "</p>";
        }
    });
});