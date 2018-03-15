<?php
/***************************************
 * www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: chatbot/addons/load_addons.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: this file contains the calls to include addon functions
 ***************************************/

//load the word censor functions
require_once("custom_tags/custom_tags.php");
require_once("word_censor/word_censor.php");
require_once('spell_checker/spell_checker.php');
require_once("parseBBCode/parseBBCode.php"); // A new addon to allow parsing of output that's consistent with BBCode tags
//require_once("checkForBan/checkForBan.php"); // A new addon for verifying that a user has not been banned by IP address

runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading addons", 4);

/**
 * Function run_pre_input_addons
 *
 * @param $say
 * @return void
 */
function run_pre_input_addons($say)
{
    global $format;

    $say = (USE_SPELL_CHECKER) ? run_spell_checker_say($say) : $say;
    #if ($format == 'html') $say =  parseInput($say);
    $_SESSION['convoArr']['say'] = $say;
}

/**
 * Function run_mid_level_addons
 *
 * @return mixed
 */
function run_mid_level_addons()
{
}

/**
 * Function run_post_response_useraddons
 *
 * @return mixed
 */
function run_post_response_useraddons()
{
    $format = _strtolower($_SESSION['convoArr']['conversation']['format']);
    $response = (isset($_SESSION['convoArr']['send_to_user'])) ? $_SESSION['convoArr']['send_to_user'] : $_SESSION['convoArr']['conversation']['error_response'];
    $curTime = date('H:i:s');
    $response = str_replace('[serverTime]', $curTime, $response);

    if ($_SESSION['convoArr']['send_to_user'] != $response) {
        $_SESSION['convoArr']['send_to_user'] = $response;
    }
     run_censor();
    if ($format == 'html') {
        checkForParsing();
    }

    $ip = $_SESSION['convoArr']['client_properties']['ip_address'];
    //if ($_SESSION['convoArr']['client_properties']['banned'] === true) add_to_ban($ip);
}