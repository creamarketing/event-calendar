<?php

global $lang;

if(array_key_exists('fi_FI', $lang) && is_array($lang['fi_FI'])) {
    $lang['fi_FI'] = array_merge($lang['en_US'], $lang['fi_FI']);
} else {
    $lang['fi_FI'] = $lang['en_US'];
}

/* Internal messenging */
$lang['fi_FI']['IM_Message']['SUBJECT'] = 'Aihe';
$lang['fi_FI']['IM_Message']['BODY'] = 'Viesti';
$lang['fi_FI']['IM_Message']['FROM'] = 'Lähettäjä';
$lang['fi_FI']['IM_Message']['TO'] = 'Vastaanottaja';
$lang['fi_FI']['IM_Message']['DATE'] = 'Päivämäärä';
$lang['fi_FI']['IM_Message']['REPLY'] = 'Vastaa';
$lang['fi_FI']['IM_Message']['REPLY_PREPEND'] = 'Vs';
$lang['fi_FI']['IM_Message']['TRASH'] = 'Siirrä roskakoriin';
$lang['fi_FI']['IM_Message']['DELETE'] = 'Poista';
$lang['fi_FI']['IM_Message']['RESTORE'] = 'Siirrä roskakorista';
$lang['fi_FI']['IM_Message']['SEND'] = 'Lähetä';
$lang['fi_FI']['IM_Message']['WROTE'] = 'kirjoitti';
$lang['fi_FI']['IM_Message']['SYSTEM_SENDER'] = 'Systeemi';
$lang['fi_FI']['IM_Message']['UNKNOWN_SENDER'] = 'Tuntematon lähettäjä';
$lang['fi_FI']['IM_Message']['UNKNOWN_RECIPIENT'] = 'Tuntematon vastaanottaja';

$lang['fi_FI']['IM_MessageBox']['INBOX'] = 'Postilaatikko';
$lang['fi_FI']['IM_MessageBox']['SENTBOX'] = 'Lähetetyt';
$lang['fi_FI']['IM_MessageBox']['TRASHBOX'] = 'Roskakori';

$lang['fi_FI']['IM_Controller']['LOADING'] = 'Ladataan';
$lang['fi_FI']['IM_Controller']['NEWMESSAGEBUTTON'] = 'Kirjoita uusi viesti';
$lang['fi_FI']['IM_Controller']['SEARCH'] = 'Hae';
$lang['fi_FI']['IM_Controller']['DISPLAYING'] = 'Näytetään';
$lang['fi_FI']['IM_Controller']['TO'] = '-';
$lang['fi_FI']['IM_Controller']['OF'] = '/';
$lang['fi_FI']['IM_Controller']['NEWMESSAGE'] = 'Uusi viesti';
$lang['fi_FI']['IM_Controller']['MARKALL'] = 'Valitse kaikki';
$lang['fi_FI']['IM_Controller']['UNMARKALL'] = 'Poista kaikki';

$lang['fi_FI']['IM_MemberDecorator']['EMAILNOTIFICATION'] = 'Lähetä kopio sisäisestä viestistä sähköpostiin';
$lang['fi_FI']['IM_NotificationEmail']['GREETING'] = 'Hei';
$lang['fi_FI']['IM_NotificationEmail']['RECEIVEDTEXT'] = 'On saapunut sisäinen viesti, jonka sisältö on seuraava';
$lang['fi_FI']['IM_NotificationEmail']['REPLYTEXT'] = 'Kirjaudu sisään <a href="%s">tästä</a>.';

?>
