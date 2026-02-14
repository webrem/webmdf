<h2>📷 Scan code-barres</h2>
<video id="video" width="300"></video>
<script src="https://unpkg.com/quagga/dist/quagga.min.js"></script>
<script>
Quagga.init({
    inputStream: { type : "LiveStream", target: document.querySelector('#video') },
    decoder: { readers: ["ean_reader","code_128_reader"] }
}, function(err) {
    if (!err) Quagga.start();
});
Quagga.onDetected(data => {
    alert("Code détecté : " + data.codeResult.code);
});
</script>
