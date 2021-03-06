<?php
/*************************************
 * Autor: mgp25                      *
 * Github: https://github.com/mgp25  *
 *************************************/
require_once dirname(__FILE__) . '/../src/whatsprot.class.php';
//Change the time zone if you are in a different country
date_default_timezone_set('Europe/Madrid');

echo "####################################\n";
echo "#                                  #\n";
echo "#           WA CLI CLIENT          #\n";
echo "#                                  #\n";
echo "####################################\n\n";
echo "====================================\n";

////////////////CONFIGURATION///////////////////////
////////////////////////////////////////////////////
$username = "573046035177";
$nickname = "ClickDelivery";
$password = "l1U0VOUbY9jcympy4Rrc27TVvHQ=";
$debug = true;
/////////////////////////////////////////////////////
if ($_SERVER['argv'][1] == null) {
    echo 'USAGE: php '.$_SERVER['argv'][0]." <number> \n\nEj: php client.php 34123456789\n\n";
    exit(1);
}
$target = $_SERVER['argv'][1];
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

$w->connect(); // Nos conectamos a la red de WhatsApp
$w->loginWithPassword($password); // Iniciamos sesion con nuestra contraseña
echo "[*]Conectado a WhatsApp\n\n";
$w->sendGetServerProperties(); // Obtenemos las propiedades del servidor
$w->sendClientConfig(); // Enviamos nuestra configuración al servidor
$sync = array($target);
$w->sendSync($sync); // Sincronizamos el contacto
$w->pollMessage(); // Volvemos a poner en cola mensajes
$w->sendPresenceSubscription($target); // Nos suscribimos a la presencia del usuario

$pn = new ProcessNode($w, $target);
$w->setNewMessageBind($pn);

/*
////////////// GROUPS
//Create
$participants = array("573004359116", "573174795431", "573192517183");
$group = $w->sendGroupsChatCreate("Alarmas", $participants);
var_dump($group);

//Send message
$gId = '573046035177-1461256429';
$w->sendMessage($gId, "Alarms!");
echo "--------------\n\n";

//Promote Participant
$w->sendPromoteParticipants($gId, "573192517183");
*/

while (1) {
    $w->pollMessage();
    $msgs = $w->getMessages();
    foreach ($msgs as $m) {
        // process inbound messages
        //print($m->NodeString("") . "\n");
    }
    $line = fgets_u(STDIN);
    if ($line != '') {
        if (strrchr($line, ' ')) {
            $command = trim(strstr($line, ' ', true));
        } else {
            $command = $line;
        }
        switch ($command) {
            case '/query':
                $dst = trim(strstr($line, ' ', false));
                echo "[] Interactive conversation with $contact:\n";
                break;
            case '/lastseen':
                echo "[] Last seen $target: ";
                $w->sendGetRequestLastSeen($target);
                break;
            default:
                $w->sendMessage($target, $line);
                break;
        }
    }
}

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
