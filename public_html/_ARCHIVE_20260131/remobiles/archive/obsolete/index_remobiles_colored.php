
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Calcul Prix R.E.Mobiles</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #c62828;
            text-align: center;
        }
        form {
            background: #000;
            padding: 20px;
            border-radius: 10px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            color: white;
        }
        label {
            display: block;
            margin-top: 15px;
            color: #ffffff;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }
        input:focus, select:focus {
            outline: none;
            box-shadow: 0 0 5px #4caf50;
        }
        button {
            background-color: #2e7d32;
            color: #fff;
            padding: 12px 20px;
            margin-top: 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #43a047;
        }
        iframe {
            width: 100%;
            height: 500px;
            margin-top: 30px;
            border: 4px solid #c62828;
            border-radius: 10px;
            display: none;
        }
    </style>
</head>
<body>

<h2>🔧 CALCUL PRIX <span style="color:#2e7d32;">R.E.</span><span style="color:#c62828;">MOBILES</span></h2>

<form id="prixForm">
    <label for="clientNom">Nom & Prénom du client :</label>
    <input type="text" name="clientNom" required>

    <label for="clientTel">Téléphone du client :</label>
    <input type="text" name="clientTel" required>

    <label for="docType">Type de document :</label>
    <select name="docType">
        <option value="DEVIS">DEVIS</option>
        <option value="PROFORMA">PROFORMA</option>
        <option value="FACTURE">FACTURE</option>
    </select>

    <label for="piece">Nom de la pièce :</label>
    <input type="text" name="piece" required>

    <label for="prixAchat">Prix d'achat unitaire (€) :</label>
    <input type="number" step="0.01" name="prixAchat" required>

    <label for="quantite">Quantité :</label>
    <input type="number" name="quantite" value="1" required>

    <label for="mainOeuvre">Main d'œuvre (€) :</label>
    <input type="number" step="0.01" name="mainOeuvre" required>

    <button type="submit">📄 Générer le ticket</button>
</form>

<iframe id="ticketFrame"></iframe>

<script>
document.getElementById("prixForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("generate_pdf.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error("Erreur PDF");
        return response.blob();
    })
    .then(blob => {
        const url = URL.createObjectURL(blob);
        const iframe = document.getElementById("ticketFrame");
        iframe.src = url;
        iframe.style.display = "block";
    })
    .catch(err => {
        alert("Erreur lors de la génération du PDF : " + err.message);
    });
});
</script>

</body>
</html>
