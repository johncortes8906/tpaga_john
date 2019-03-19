<?php

/*************************************
 * Autor: mgp25                      *
 * Github: https://github.com/mgp25  *
 *************************************/
require_once dirname(__FILE__) . '/../src/whatsprot.class.php';
//Change the time zone if you are in a different country
date_default_timezone_set('America/Bogota');

////////////////CONFIGURATION///////////////////////
////////////////////////////////////////////////////
$target = "573004359116";
$gId = '573046035177-1461256429';
$username = "573046035177";
$nickname = "ClickDelivery";
$password = "l1U0VOUbY9jcympy4Rrc27TVvHQ=";
$debug = true;
/////////////////////////////////////////////////////
if ($_SERVER['argv'][1] == null) {
    echo 'USAGE: php '.$_SERVER['argv'][0]." <msg> \n\nEj: php client.php \"Cool App!\"\n\n";
    exit(1);
}
$msg = $_SERVER['argv'][1];
function fgets_u($pStdn)
{
    $pArr = array($pStdn);

    if (false === ($num_changed_streams = stream_select($pArr, $write = null, $except = null, 0))) {
        echo "\$ 001 Socket Error : UNABLE TO WATCH STDIN.\n";

        return false;
    } elseif ($num_changed_streams > 0) {
        return trim(fgets($pStdn, 1024));
    }

    return;
}

function onPresenceAvailable($username, $from)
{
    $dFrom = str_replace(array('@s.whatsapp.net', '@g.us'), '', $from);
    echo "<$dFrom is online>\n\n";
}

function onPresenceUnavailable($username, $from, $last)
{
    $dFrom = str_replace(array('@s.whatsapp.net', '@g.us'), '', $from);
    echo "<$dFrom is offline>\n\n";
}

echo "[] logging in as '$nickname' ($username)\n";
$w = new WhatsProt($username, $nickname, $debug);

$w->eventManager()->bind('onPresenceAvailable', 'onPresenceAvailable');
$w->eventManager()->bind('onPresenceUnavailable', 'onPresenceUnavailable');

$connected = $w->connect(); // Nos conectamos a la red de WhatsApp
$w->loginWithPassword($password); // Iniciamos sesion con nuestra contraseña

echo "............";
var_dump($connected);
echo "............";


echo "[*]Conectado a WhatsApp\n\n";

$w->sendGetServerProperties(); // Obtenemos las propiedades del servidor
$w->sendClientConfig(); // Enviamos nuestra configuración al servidor
$sync = array($target);
$w->sendSync($sync); // Sincronizamos el contacto


$w->pollMessage(); // Volvemos a poner en cola mensajes
$w->sendPresenceSubscription($target); // Nos suscribimos a la presencia del usuario

$pn = new ProcessNode($w, $target);
$w->setNewMessageBind($pn);


$w->pollMessage();
$msgs = $w->getMessages();
foreach ($msgs as $m) {
    // process inbound messages
    // print($m->NodeString("") . "\n");
}

$line = $msg;

echo ">> " . $line . "\n\n";

//$sent = $w->sendMessage($target, $line);
$sent = $w->sendMessage($gId, $line);

var_dump($sent);

echo "//////////// PINGING ..............\n\n";

//Don't ask... Ted... just... okay?
$ping = $w->sendPing();
sleep(1);
$ping = $w->sendPing();
sleep(1);
$ping = $w->sendPing();
sleep(1);


class ProcessNode
{
    protected $wp = false;
    protected $target = false;

    public function __construct($wp, $target)
    {
        $this->wp = $wp;
        $this->target = $target;
    }

    public function process($node)
    {
        $text = $node->getChild('body');
        $text = $text->getData();
        $notify = $node->getAttribute('notify');

        echo "\n- ".$notify.': '.$text.'    '.date('H:i')."\n";
    }
}
