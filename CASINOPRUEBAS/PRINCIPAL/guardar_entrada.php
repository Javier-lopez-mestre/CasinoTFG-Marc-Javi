<?php
session_start();

$data=json_decode(file_get_contents("php://input"),true);

$_SESSION['entrada_juego']=floatval($data['entrada']);

echo json_encode(["ok"=>true]);