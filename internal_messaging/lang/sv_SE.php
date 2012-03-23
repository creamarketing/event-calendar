<?php

global $lang;

if(array_key_exists('sv_SE', $lang) && is_array($lang['sv_SE'])) {
    $lang['sv_SE'] = array_merge($lang['en_US'], $lang['sv_SE']);
} else {
    $lang['sv_SE'] = $lang['en_US'];
}

/* Internal messenging */
$lang['sv_SE']['IM_Message']['SUBJECT'] = 'Ämne';
$lang['sv_SE']['IM_Message']['BODY'] = 'Meddelande';
$lang['sv_SE']['IM_Message']['FROM'] = 'Avsändare';
$lang['sv_SE']['IM_Message']['TO'] = 'Mottagare';
$lang['sv_SE']['IM_Message']['DATE'] = 'Datum';
$lang['sv_SE']['IM_Message']['REPLY'] = 'Svara';
$lang['sv_SE']['IM_Message']['REPLY_PREPEND'] = 'Sv';
$lang['sv_SE']['IM_Message']['TRASH'] = 'Flytta till papperskorg';
$lang['sv_SE']['IM_Message']['DELETE'] = 'Radera';
$lang['sv_SE']['IM_Message']['RESTORE'] = 'Flytta från papperskorg';
$lang['sv_SE']['IM_Message']['SEND'] = 'Skicka';
$lang['sv_SE']['IM_Message']['WROTE'] = 'skrev';
$lang['sv_SE']['IM_Message']['SYSTEM_SENDER'] = 'Systemet';
$lang['sv_SE']['IM_Message']['UNKNOWN_SENDER'] = 'Okänd avsändare';
$lang['sv_SE']['IM_Message']['UNKNOWN_RECIPIENT'] = 'Okänd mottagare';

$lang['sv_SE']['IM_MessageBox']['INBOX'] = 'Inkorg';
$lang['sv_SE']['IM_MessageBox']['SENTBOX'] = 'Skickade';
$lang['sv_SE']['IM_MessageBox']['TRASHBOX'] = 'Papperskorg';

$lang['sv_SE']['IM_Controller']['LOADING'] = 'Laddar';
$lang['sv_SE']['IM_Controller']['NEWMESSAGEBUTTON'] = 'Skriv nytt meddelande';
$lang['sv_SE']['IM_Controller']['SEARCH'] = 'Sök';
$lang['sv_SE']['IM_Controller']['DISPLAYING'] = 'Visar';
$lang['sv_SE']['IM_Controller']['TO'] = 'till';
$lang['sv_SE']['IM_Controller']['OF'] = 'av';
$lang['sv_SE']['IM_Controller']['NEWMESSAGE'] = 'Nytt meddelande';
$lang['sv_SE']['IM_Controller']['MARKALL'] = 'Markera alla';
$lang['sv_SE']['IM_Controller']['UNMARKALL'] = 'Avmarkera alla';

$lang['sv_SE']['IM_MemberDecorator']['EMAILNOTIFICATION'] = 'Skicka kopia av interna meddelanden till epost';
$lang['sv_SE']['IM_NotificationEmail']['GREETING'] = 'Hej';
$lang['sv_SE']['IM_NotificationEmail']['RECEIVEDTEXT'] = 'Det har anlänt ett internt meddelande som innehåller följande';
$lang['sv_SE']['IM_NotificationEmail']['REPLYTEXT'] = 'Du kan logga in <a href="%s">här</a>.';

?>
