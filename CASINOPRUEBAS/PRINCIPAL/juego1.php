<?php
session_start();
include("conexion.php");

if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php");
    exit();
}

$id = $_SESSION['id_usuario'];

$stmt = $conexion->prepare("SELECT saldo FROM usuarios WHERE id_usuario=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$saldo = $stmt->get_result()->fetch_assoc()['saldo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blackjack Pro</title>

<style>

body{
    margin:0;
    font-family:Arial;
    background:url("img/blackjack_table.jpg") center/cover no-repeat;
    color:white;
}

/* 🔥 BOTÓN VOLVER PRO */
#backBtn{
    position:fixed;
    top:12px;
    left:12px;
    z-index:99999;
    padding:10px 14px;
    border-radius:8px;
    border:1px solid white;
    background:black;
    color:white;
    cursor:pointer;
}

/* CONTENEDOR */
.game{
    min-height:100vh;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    background:rgba(0,0,0,0.65);
    padding:15px;
    text-align:center;
}

/* CARTAS PRO */
.cards{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    justify-content:center;
    margin:10px 0;
}

.cards img{
    width:60px;
    border-radius:6px;
    box-shadow:0 2px 6px rgba(0,0,0,0.5);
}

/* FICHAS PRO */
.chips{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:center;
    margin:10px;
}

.chip{
    width:50px;
    height:50px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    cursor:pointer;
    border:2px solid white;
    background:radial-gradient(circle,#ff4444,#990000);
}

/* BOTONES */
button{
    padding:10px;
    margin:5px;
    border-radius:6px;
    border:none;
    cursor:pointer;
}

/* POPUP */
#popup{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.92);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.box{
    background:#222;
    padding:20px;
    border-radius:10px;
    width:90%;
    max-width:320px;
}

</style>
</head>

<body>

<button id="backBtn" onclick="exitGame()">🏠 Salir</button>

<!-- POPUP INICIAL -->
<div id="popup">
    <div class="box">
        <h3>Configurar sesión</h3>
        <p>Saldo real: <?= $saldo ?>€</p>

        <input type="number" id="bankroll" placeholder="Dinero sesión">
        <input type="number" id="time" placeholder="Minutos (5-60)">

        <button onclick="start()">Empezar</button>
    </div>
</div>

<!-- JUEGO -->
<div class="game">

<h2>Bankroll: <span id="bankrollShow">0</span>€</h2>
<h3>Apuesta: <span id="bet">0</span>€</h3>

<div class="chips">
    <div class="chip" onclick="add(1)">1</div>
    <div class="chip" onclick="add(5)">5</div>
    <div class="chip" onclick="add(10)">10</div>
    <div class="chip" onclick="add(25)">25</div>
</div>

<button onclick="clearBet()">Limpiar</button>
<button onclick="newHand()">Nueva mano</button>

<h3>Jugador (<span id="pj">0</span>)</h3>
<div class="cards" id="player"></div>

<h3>Crupier (<span id="pc">0</span>)</h3>
<div class="cards" id="dealer"></div>

<h2 id="result"></h2>

<button onclick="hit()">Pedir</button>
<button onclick="stand()">Plantarse</button>

</div>

<script>

let bankroll=0;
let bet=0;
let player=[];
let dealer=[];
let lock=true;
let timer;

/* 🔥 START */
function start(){
    bankroll=parseFloat(document.getElementById("bankroll").value);
    let t=parseInt(document.getElementById("time").value);

    if(bankroll<=0||isNaN(bankroll)) return alert("Bankroll mal");
    if(t<5||t>60) return alert("Tiempo mal");

    document.getElementById("popup").style.display="none";
    document.getElementById("bankrollShow").innerText=bankroll;

    lock=false;
}

/* 🔥 CARTAS */
function card(){
    let c=["2","3","4","5","6","7","8","9","10","J","Q","K","A"];
    return c[Math.floor(Math.random()*c.length)];
}

function val(c){
    if(c=="J"||c=="Q"||c=="K") return 10;
    if(c=="A") return 11;
    return parseInt(c);
}

function sum(arr){
    let t=0, aces=0;

    arr.forEach(c=>{
        t+=val(c);
        if(c=="A") aces++;
    });

    while(t>21&&aces>0){
        t-=10;
        aces--;
    }

    return t;
}

/* 🔥 APUESTA */
function add(v){
    if(lock) return;
    if(bet+v>bankroll) return alert("Sin saldo");
    bet+=v;
    document.getElementById("bet").innerText=bet;
}

function clearBet(){
    bet=0;
    document.getElementById("bet").innerText=0;
}

/* 🔥 MANO */
function newHand(){
    if(bet<=0) return alert("Apuesta primero");

    player=[card(),card()];
    dealer=[card()];

    render();
    document.getElementById("result").innerText="";
}

/* 🔥 HIT */
function hit(){
    player.push(card());
    render();

    if(sum(player)>21) finish("LOSE");
}

/* 🔥 STAND */
function stand(){
    while(sum(dealer)<17) dealer.push(card());

    let p=sum(player);
    let d=sum(dealer);

    if(p>d||d>21) finish("WIN");
    else if(p==d) finish("PUSH");
    else finish("LOSE");
}

/* 🔥 FIN */
function finish(r){

    if(r=="WIN") bankroll+=bet;
    if(r=="LOSE") bankroll-=bet;

    document.getElementById("bankrollShow").innerText=bankroll;
    document.getElementById("result").innerText=r;

    bet=0;
    player=[];
    dealer=[];
}

/* 🔥 RENDER */
function render(){

    document.getElementById("player").innerHTML=
        player.map(c=>`<img src="img/cards/${c}.png">`).join("");

    document.getElementById("dealer").innerHTML=
        dealer.map(c=>`<img src="img/cards/${c}.png">`).join("");

    document.getElementById("pj").innerText=sum(player);
    document.getElementById("pc").innerText=sum(dealer);
}

/* 🔥 SALIR */
function exitGame(){
    fetch("salir_blackjack.php",{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body:JSON.stringify({bankroll})
    }).then(()=>location.href="principal.php");
}

</script>

</body>
</html>