<?php

session_start();


const CARPETA_IMATGES = "Cartes"; // carpeta on estan les imatges de les cartes
const MAX_RONDES = 1000; //aixi evitem partides infinites

$PALS   = ["Copes", "Oros", "Espases", "Bastos"];
$VALORS = ["Dos", "Tres", "Quatre", "Cinc", "Sis", "Set", "Vuit", "Nou", "Sota", "Cavall", "Rei", "As"];
//FUNCIONS PROPIES
//
function crearBaralla() {
    global $PALS, $VALORS;
    $baralla = [];
    foreach ($PALS as $pal) {
        foreach ($VALORS as $valor) {
            $baralla[] = ["pal" => $pal, "valor" => $valor];
        }
    }
    shuffle($baralla);// barreja l'array en lloc (barreja l'ordre aleatòriament)
    return $baralla;
}
// Reparteix les cartes de la baralla entre els dos jugadors
// Retorna un array amb dos mans (array de cartes) per a cada jugador
//array_shift treu el primer element de l'array i el retorna, i redueix l'array(com si agafares la carta de dalt del teu montó)
//com son 48 cartes, cada jugador tindrà 24 cartes
//retorna: ma1 i ma2, les mans dels dos jugadors
function repartirCartes($baralla) {
    $ma1= [];
    $ma2= [];
    while (count($baralla) > 0) {
        $ma1[] = array_shift ($baralla);
        $ma2[] = array_shift ($baralla);
    }
    return [$ma1, $ma2];
}
//el valor de cada carta es basa en la seua posició dins de VALORS
//array_search retorna l'index on es troba el valor, per exemple Dos= 0, i el As=13, per a no escriure el valor de les cartes a mà
//retorna un enter de 0 a 11
function valorCarta(array $carta, array $valors): int
{
    return (int) array_search($carta["valor"], $valors);
}
//compara el valor de dos cartes amb l'operador <=>
//retorna 1 si guanya la primera, -2 si guanya la segona mà, i 0 si empaten
function compararCartes(array $sc1, array $sc2, array $valors): int
{
    return valorCarta($sc1,$valors) <=> valorCarta($sc2,$valors);
}
//decideix qui guanya segons les cartes que te cada jugador (qui en té més)
//es fa servir al final de la partida i quan algú no pot continuar una guerra
//retorna 1, 2 o null si tenen les mateixes cartes (empat)
function guanyadorPerCartes(array $ma1, array $ma2): ?int
{
    if (count($ma1) > count($ma2)) return 1;
    if (count($ma2) > count($ma1)) return 2;
    return null;
}
//nova partida , prepara una partida nova, crea la baralla, la barreja i la repateix
//retorna l'arrai d'estat de la partida, que es guarda a S_SESSION
function novaPartida(array $pals, array $valors): array
{
    $baralla = crearBaralla();
    shuffle($baralla);
    [$ma1, $ma2] = repartirCartes($baralla);
    return [
        "ma1"       => $ma1,
        "ma2"       => $ma2,
        "ronda"     => 0,
        "ultima"    => null,   // detalls de l'última ronda per a pintar-la
        "final"     => false,
        "guanyador" => null,
    ];
}
function jugarRonda(array &$estat, array $valors): void
{
    $estat["ronda"]++;
        // Cada jugador mostra la carta de dalt del seu munt
    $c1 = array_shift($estat["ma1"]);
    $c2 = array_shift($estat["ma2"]);
    $inicial    = ["c1" => $c1, "c2" => $c2]; // primeres cartes de la ronda (per a pintar)
    $botiCartes = [$c1, $c2];                 // cartes en joc (se les emporta el guanyador)
    $guerres    = [];                         // registre de les guerres per a mostrar-les
    $guerraSenseCartes = false;               // true si algú no pot continuar una guerra
    $resultat   = compararCartes($c1, $c2, $valors);
    while ($resultat === 0) {
        if (count($estat["ma1"]) < 2 || count($estat["ma2"]) < 2) {
            // Un jugador no pot continuar la guerra: perd la partida.
            // El rival (el que té més cartes) s'ho emporta tot; si igualen, empat.
            $guanyaGuerra = guanyadorPerCartes($estat["ma1"], $estat["ma2"]);
            $totes = array_merge($botiCartes, $estat["ma1"], $estat["ma2"]);
            $estat["ma1"] = ($guanyaGuerra === 1) ? $totes : [];
            $estat["ma2"] = ($guanyaGuerra === 2) ? $totes : [];
            $botiCartes   = [];
            $resultat     = $guanyaGuerra === 1 ? 1 : ($guanyaGuerra === 2 ? -1 : 0);
            $guerraSenseCartes = true;
            break;
        }
        $tapada1 = array_shift($estat["ma1"]);
        $tapada2 = array_shift($estat["ma2"]);
        $c1      = array_shift($estat["ma1"]);
        $c2      = array_shift($estat["ma2"]);
        $botiCartes = array_merge($botiCartes, [$tapada1, $tapada2, $c1, $c2]);
        $guerres[]  = ["c1" => $c1, "c2" => $c2];   // cartes destapades de cada guerra
        $resultat   = compararCartes($c1, $c2, $valors);
    }


    // El guanyador de la ronda s'emporta el botí (remenat per evitar bucles infinits)
    if ($resultat !== 0) {
        shuffle($botiCartes);
        $clau = ($resultat > 0) ? "ma1" : "ma2";
        $estat[$clau] = array_merge($estat[$clau], $botiCartes);
        $guanyadorRonda = ($resultat > 0) ? 1 : 2;
    } else {
        $guanyadorRonda = null; // empat total (cap guanya)
    }
    $nBoti = count($botiCartes);

    // Guardem les dades de l'última ronda per a pintar-la
    $estat["ultima"] = [
        "inicial"   => $inicial,
        "guerres"   => $guerres,
        "guanyador" => $guanyadorRonda,
        "boti"      => $nBoti,
        "sensecartes" => $guerraSenseCartes,
    ];
    // Detecció del final de partida
    comprovarFinal($estat);
}
//mira si la partida ha acabat: algun jugador sense cartes o s'han arribat a MAX_RONDES 
//en eixe cas marca final =true i anota el guanyador que es qui te mes cartes

function comprovarFinal(array &$estat): void
{
    $n1 = count($estat["ma1"]);
    $n2 = count($estat["ma2"]);

    if ($n1 === 0 || $n2 === 0 || $estat["ronda"] >= MAX_RONDES) {
        $estat["final"]     = true;
        $estat["guanyador"] = guanyadorPerCartes($estat["ma1"], $estat["ma2"]);
    }
}
//generar la carta en format HTML
//nom del fitxer: "copes As.jpg" (per exemple)
//com te un espai s'usa rawurlencode per a que no hi haja problemes amb el nom del fitxer
//htmlspecialchars escapa els caracters especials abans de posarl-los a l'HTML

function pintarCarta(array $carta): string
{
    $fitxer = $carta["pal"] . " " . $carta["valor"] . ".jpg";
    $ruta   = CARPETA_IMATGES . "/" . rawurlencode($fitxer);
    $alt    = $carta["valor"] . " de " . $carta["pal"];
    return '<img class="carta" src="' . htmlspecialchars($ruta) . '" alt="' . htmlspecialchars($alt) . '">';
}

//Genera l'HTML del revers d'una carta (carta tapada).
//ho faig en un <di> amb estil css (patro blau), per que no hi han imatges del revers.

function pintarRevers(): string
{
    return '<div class="carta revers" title="Carta tapada"></div>';
}

// CONTROLADOR (estat amb sessions) 
//esta part s'executa cada vegada que es carrega la pagina o es pulsa un boto.

// Nova partida: al principi, o quan es pulsa el botó "Nova partida"
if (!isset($_SESSION["estat"]) || isset($_POST["nova"])) {
    $_SESSION["estat"] = novaPartida($PALS, $VALORS);
}

// Jugar ronda (només si la partida no ha acabat)
if (isset($_POST["jugar"]) && !$_SESSION["estat"]["final"]) {
    jugarRonda($_SESSION["estat"], $VALORS);
}

// Jugar fins al final: repeteix rondes fins que la partida acaba
if (isset($_POST["auto"])) {
    while (!$_SESSION["estat"]["final"]) {
        jugarRonda($_SESSION["estat"], $VALORS);
    }
}

// Patró Post/Redirect/Get: evita reenviar el formulari en recarregar la pàgina
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// VISTA (HTML)
//agafem l'estat de la sessio en variables curtes per a usar-les a l'HTML

$estat = $_SESSION["estat"];
$ult   = $estat["ultima"];
?>
<!--fi del PHP, comença l'HTML, que he fet amb el css intern per a simplificar-ho tot.!-->

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Joc de la Guerra</title>
    <style>
body {
            font-family: monospace;
            background: #04460d;
            color: #d3b925;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-size: 1.2rem;
            font-family: monospace ;
            margin-bottom: 10px;
        }

        .missatge {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 20px;
        }

        .marcador {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            width: 100%;
            max-width: 400px;
        }

        .zona {
            flex: 1;
            background: #222;
            border: 1px solid #444;
            padding: 10px;
            text-align: center;
        }

        .taula {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            background: #222;
            border: 1px solid #444;
            padding: 20px;
            margin-bottom: 20px;
            width: 100%;
            max-width: 400px;
        }

        .carta {
            width: 70px;
            height: 100px;
            background: #fff;
            border: 1px solid #000;
        }

        .revers {
            background: #0044cc;
        }

        .controls {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 400px;
        }

        button {
            flex: 1;
            font-family: monospace;
            padding: 10px;
            background: #fff;
            color: #111;
            border: 1px solid #fff;
            cursor: pointer;
        }

        button:hover {
            background: #ccc;
        }
    </style>
</head>
<body>
    <h1>Guerra: Jorge Sendra DWES</h1>

    <!-- Marcador: cartes de cada jugador i número de ronda -->

    <div class="marcador">
        <div class="zona">Tu<br><strong><?= count($estat["ma1"]) ?></strong> cartes</div>
        <div class="zona">Ronda<br><strong><?= (int) $estat["ronda"] ?></strong></div>
        <div class="zona">Màquina<br><strong><?= count($estat["ma2"]) ?></strong> cartes</div>
    </div>

    <!-- Si encara no s'ha jugat cap ronda, es mostra un missatge d'inici; si no, es pinten les cartes de l'última ronda -->

    <?php if ($ult === null): ?>
        <p class="missatge">Cada jugador té 24 cartes. Prem «Jugar ronda» per a començar!</p>
    <?php else: ?>
        <div class="taula">
            <div>Tu<br><?= pintarCarta($ult["inicial"]["c1"]) ?></div>
            <div>contra</div>
            <div>Màquina<br><?= pintarCarta($ult["inicial"]["c2"]) ?></div>
        </div>

        <!-- Si hi ha hagut guerres, es pinta cada una: una carta tapada + una destapada per jugador -->

        <?php foreach ($ult["guerres"] as $num => $guerra): ?>
            <div class="guerra">
                <strong>⚔️ Guerra <?= $num + 1 ?>!</strong> (una carta tapada i una destapada cada un)
                <div class="taula">
                    <div><?= pintarRevers() ?><?= pintarCarta($guerra["c1"]) ?></div>
                    <div>contra</div>
                    <div><?= pintarRevers() ?><?= pintarCarta($guerra["c2"]) ?></div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Missatge sobre qui ha guanyat la ronda i quantes cartes s'emporta --></p>

        <p class="missatge">
            <?php if ($ult["sensecartes"]): ?>
                Un jugador s'ha quedat sense cartes per a continuar la guerra.
            <?php elseif ($ult["guanyador"] === 1): ?>
                Guanyes la ronda i t'emportes <?= (int) $ult["boti"] ?> cartes.
            <?php elseif ($ult["guanyador"] === 2): ?>
                La màquina guanya la ronda i s'emporta <?= (int) $ult["boti"] ?> cartes.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<!-- Final de partida: es mostra el guanyador quan $estat["final"] és true -->

    <?php if ($estat["final"]): ?>
        <div class="final">
            <?php if ($estat["guanyador"] === 1): ?>
                Has guanyat la partida!
            <?php elseif ($estat["guanyador"] === 2): ?>
                Ha guanyat la màquina.
            <?php else: ?>
                Empat!
            <?php endif; ?>
            <div style="font-size:.6em">en <?= (int) $estat["ronda"] ?> rondes</div>
        </div>
    <?php endif; ?>
    <!-- Botons: cada un envia un POST amb el seu "name", que el controlador detecta amb isset(). Si la partida ha acabat, s'amaguen els botons de jugar i només queda "Nova partida". -->
    <form method="post">
        <?php if (!$estat["final"]): ?>
            <button class="btn-jugar" type="submit" name="jugar">Jugar ronda</button>
            <button class="btn-jugar" type="submit" name="auto">Jugar fins al final</button>
        <?php endif; ?>
        <button class="btn-nova" type="submit" name="nova">Nova partida</button>
    </form>
    <footer>
        <p>2026 Jorge Sendra Noguera. 2n DAW Semipresencial.</p>
    </footer>
</body>
</html>
