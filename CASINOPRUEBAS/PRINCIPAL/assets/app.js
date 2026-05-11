/* JS compartido del frontend */

function toggleIngresar(){
  const el = document.getElementById('ingresar-menu');
  if (el) el.classList.toggle('hidden');
}

function togglePerfil(){
  const el = document.getElementById('perfil-menu');
  if (el) el.classList.toggle('hidden');
}

document.addEventListener('click', function(e){
  const perfilContainer = document.getElementById('perfil-container');
  const ingresarContainer = document.getElementById('ingresar-container');

  if (perfilContainer && !perfilContainer.contains(e.target)) {
    const menu = document.getElementById('perfil-menu');
    if (menu) menu.classList.add('hidden');
  }
  if (ingresarContainer && !ingresarContainer.contains(e.target)) {
    const menu = document.getElementById('ingresar-menu');
    if (menu) menu.classList.add('hidden');
  }
});

async function pagar(monto = 10){
  const r = await fetch('crear_sesion_pago.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({monto})
  });

  const d = await r.json();
  if (d && d.url) {
    window.location.href = d.url;
    return;
  }

  alert('Error pago');
}

function pagarCustom(){
  const input = document.getElementById('montoCustom');
  const monto = input ? parseFloat(input.value) : NaN;

  if (Number.isNaN(monto) || monto < 1) {
    alert('Mínimo 1€');
    return;
  }
  if (monto > 1000) {
    alert('Máximo 1000€');
    return;
  }

  pagar(monto);
}

async function actualizarSaldoPrincipal(){
  const el = document.getElementById('casino-saldo');
  if (!el) return;

  const r = await fetch('saldo.php', {cache: 'no-store'});
  const data = await r.json();

  // principal.php espera texto en formato custom
  // Solo renderizamos saldo si existe.
  const saldo = parseFloat(data.saldo ?? 0);
  el.textContent = 'Saldo: $' + saldo.toFixed(2);
}

async function actualizarSaldosPerfil(){
  const r = await fetch('saldo.php', {cache: 'no-store'});
  const data = await r.json();

  const saldo = parseFloat(data.saldo ?? 0);
  const total = parseFloat(data.total_dinero ?? 0);

  const saldoEls = document.querySelectorAll('.saldo-disponible');
  saldoEls.forEach(x => x.textContent = saldo.toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2}));

  const totalEls = document.querySelectorAll('.total-dinero');
  totalEls.forEach(x => x.textContent = total.toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2}));
}

function startSaldoPolling(intervalMs = 10000){
  // Reduce polling para mejor rendimiento.
  // Se puede ajustar por página.
  setInterval(() => {
    actualizarSaldoPrincipal().catch(()=>{});
  }, intervalMs);
}

