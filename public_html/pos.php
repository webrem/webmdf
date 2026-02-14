<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';

// pos.php
include 'header.php';
session_start();
require_once __DIR__ . '/sync_time.php'; // ⏱ Sync heure automatique
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
?>
<h2 class="text-center mb-4">📊 Tableau de bord — Point de Vente</h2>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="m-0">Point de Vente</h4></div>
    <div>
      <span class="badge bg-light text-dark">
        👤 <?=htmlspecialchars($_SESSION['username'] ?? 'Utilisateur')?>
        (<?=htmlspecialchars($_SESSION['role'] ?? '')?>)
      </span>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7">
      <input id="search_product" class="form-control mb-2" placeholder="Chercher référence ou nom (2+ caractères)">
      <div id="search_results" class="list-group"></div>
    </div>

    <div class="col-md-5">
      <div class="card">
        <div class="card-body">
          <h5>Panier</h5>
          <table class="table table-sm" id="cart_table">
            <thead><tr><th>Produit</th><th>Qte</th><th>PU</th><th>Total</th><th></th></tr></thead>
            <tbody></tbody>
          </table>
          <div class="d-flex justify-content-between">
            <strong>Total :</strong><h4 id="cart_total">0.00</h4>
          </div>
          <div class="mt-2">
            <select id="payment_method" class="form-control mb-2">
              <option>espèces</option>
              <option>CB</option>
              <option>SumUp</option>
              <option>virement</option>
            </select>
            <button id="finalize_sale" class="btn btn-success w-100">Finaliser et imprimer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// POS frontend JS - utilise api_get_products.php qui renvoie {id, reference, designation, price, quantite}
let cart = [];

function formatPrice(x){ return parseFloat(x||0).toFixed(2); }

document.getElementById('search_product').addEventListener('input', async function(){
  const q = this.value.trim();
  if (q.length < 2) { document.getElementById('search_results').innerHTML = ''; return; }
  const res = await fetch('api_get_products.php?q='+encodeURIComponent(q));
  if (!res.ok) { document.getElementById('search_results').innerHTML = '<div class="text-danger p-2">Erreur recherche</div>'; return; }
  const data = await res.json();
  const list = data.map(p => {
    return `<button class="list-group-item list-group-item-action" onclick="addToCart(${p.id},'${escapeHtml(p.reference)}','${escapeHtml(p.designation)}',${p.price},${p.quantite})">
      ${escapeHtml(p.reference)} — ${escapeHtml(p.designation)} — ${formatPrice(p.price)}€ (stock ${p.quantite})
    </button>`;
  }).join('');
  document.getElementById('search_results').innerHTML = list || '<div class="p-2 text-muted">Aucun résultat</div>';
});

function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }

function addToCart(id, ref, name, pu, stock){
  const existing = cart.find(i=>i.article_id===id);
  if (existing) existing.qty += 1;
  else cart.push({article_id:id, reference: ref, designation: name, qty:1, price_unit: parseFloat(pu||0)});
  renderCart();
}

function renderCart(){
  const tbody = document.querySelector('#cart_table tbody'); tbody.innerHTML = '';
  let total = 0;
  cart.forEach((it, idx)=>{
    const lineTotal = it.qty * it.price_unit;
    total += lineTotal;
    tbody.innerHTML += `<tr>
      <td>${escapeHtml(it.designation)}</td>
      <td><input type="number" min="1" value="${it.qty}" data-idx="${idx}" class="cart-qty form-control form-control-sm" style="width:80px"></td>
      <td>${formatPrice(it.price_unit)}€</td>
      <td>${formatPrice(lineTotal)}€</td>
      <td><button class="btn btn-sm btn-danger" onclick="removeItem(${idx})">x</button></td>
    </tr>`;
  });
  document.getElementById('cart_total').innerText = formatPrice(total) + ' €';
  document.querySelectorAll('.cart-qty').forEach(el=>{
    el.addEventListener('change', (e)=>{
      const i = parseInt(e.target.dataset.idx);
      cart[i].qty = Math.max(1, parseInt(e.target.value)||1);
      renderCart();
    });
  });
}

function removeItem(idx){ cart.splice(idx,1); renderCart(); }

document.getElementById('finalize_sale').addEventListener('click', async ()=>{
  if (cart.length === 0) { alert('Panier vide'); return; }
  const payload = {
    client_id: null,
    payment_method: document.getElementById('payment_method').value,
    items: cart.map(it=>({ article_id: it.article_id, qty: it.qty, price_unit: it.price_unit }))
  };
  const res = await fetch('pos_process.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (!data.success) { alert('Erreur: ' + (data.message||'Erreur')); return; }
  window.open('generate_ticket_sale.php?sale_id='+data.sale_id, '_blank');
  cart = []; renderCart();
});
</script>
