<?php
/***************************************
 * www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: chatbot/core/conversation/make_conversation.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: this file contains the functions control the creation of the conversation
 ***************************************/

/**
 * function make_conversation()
 * A controller function to run the instructions to make the conversation
 *
 * @link http://blog.program-o.com/?p=1209
 * @return (void)
 */
function make_conversation()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Making conversation", 4);
    //get the user input and clean it
    $_SESSION['convoArr']['aiml']['lookingfor'] = normalize_text($_SESSION['convoArr']['user_say'][1]);
    //find an aiml match in the db
    get_aiml_to_parse();
    //file_put_contents(_LOG_PATH_ . 'convoArray.txt', print_r(true));
    parse_matched_aiml('normal');

    //parse the aiml to build a response
    //store the conversation
    push_on_front_convoArr('parsed_template', $_SESSION['convoArr']['aiml']['parsed_template']);
    push_on_front_convoArr('template', $_SESSION['convoArr']['aiml']['template']);
    //display conversation vars to user.
    $_SESSION['convoArr']['conversation']['totallines']++;
}

/**
 * function buildNounList()
 * loads a nounlist from file and inserts it into the convo array
 *
 * @link http://blog.program-o.com/?p=1212
 * @internal param int $person
 * @internal param string $in
 * @return void
 */

function buildNounList()
{
    $fileName = _CONF_PATH_ . 'nounList.dat';
    $nounList = file($fileName, FILE_IGNORE_NEW_LINES);
    $_SESSION['convoArr']['nounList'] = $nounList;
}


