<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: chatbot/core/conversation/display_conversation.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: this file contains the functions to handle the return of the conversation lines back to the user
 ***************************************/

/**
 * function get_conversation_to_display()
 * This function gets the conversation from the db to display/return to the user
 * @link http://blog.program-o.com/?p=1223

 * @return array $orderedRows - a list of conversation line
 **/
function get_conversation_to_display()
{
    global $dbn, $bot_name, $unknown_user;
    $orderedRows = array();

    $user_id = $_SESSION['convoArr']['conversation']['user_id'];
    $bot_id = $_SESSION['convoArr']['conversation']['bot_id'];
    $user_name = $_SESSION['convoArr']['conversation']['user_name'];
    $user_name = (!empty ($user_name)) ? $user_name : $unknown_user;
    $_SESSION['convoArr']['conversation']['bot_name'] = $bot_name;

    if (empty ($bot_name))
    {
        /** @noinspection SqlDialectInspection */
        $sql = "SELECT `bot_name` FROM `bots` WHERE `bot_id` = :bot_id limit 1;";
        $params = array(':bot_id' => $_SESSION['convoArr']['conversation']['bot_id']);
        $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);
        $bot_name = $row['bot_name'];
    }

    if ($_SESSION['convoArr']['conversation']['conversation_lines'] != 0)
    {
        $limit = " LIMIT " . $_SESSION['convoArr']['conversation']['conversation_lines'];
    }
    else {
        $limit = "";
    }

    $sql = "SELECT * FROM `$dbn`.`conversation_log` WHERE
        `user_id` = :user_id
        AND `bot_id` = :bot_id
        AND `convo_id` = :convo_id
        ORDER BY id DESC $limit";
    $params = array(
        ':bot_id'   => $_SESSION['convoArr']['conversation']['bot_id'],
        ':convo_id' => $_SESSION['convoArr']['conversation']['convo_id'],
        ':user_id'  => $_SESSION['convoArr']['conversation']['user_id'],
    );

    runDebug(__FILE__, __FUNCTION__, __LINE__, "get_conversation SQL: $sql", 3);
    $debugSQL = db_parseSQL($sql, $params);
    //save_file(_LOG_PATH_ . 'gc2dsql.txt', $debugSQL);

    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if (count($result) > 0)
    {
        foreach ($result as $row)
        {
            $allrows[] = $row;
        }
        $orderedRows = array_reverse($allrows, false);
    }
    else {
        $orderedRows[] = array('id' => NULL, 'input' => "", 'response' => "", 'user_id' => $_SESSION['convoArr']['conversation']['user_id'], 'bot_id' => $_SESSION['convoArr']['conversation']['bot_id'], 'timestamp' => "");
    }

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Found '" . count($result) . "' lines of conversation", 2);

    return $orderedRows;
}

/**
 * function get_conversation()
 * This function gets the conversation format
 * @link http://blog.program-o.com/?p=1225

 * @return void
 **/
function get_conversation()
{
    $conversation = get_conversation_to_display();
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Processing conversation as " . $_SESSION['convoArr']['conversation']['format'], 4);

    switch ($_SESSION['convoArr']['conversation']['format'])
    {
        case "html" :
            get_html($conversation);
            break;
        case "json" :
            get_json($conversation);
            break;
        case "xml" :
            get_xml($conversation);
            break;
    }
    
}

/**
 * function get_html()
 * This function formats the response as html
 * @link http://blog.program-o.com/?p=1227

 * @param  array $conversation - the conversation lines to format
 * @return void
 **/
function get_html($conversation)
{
    //$conversation = $_SESSION['convoArr']['conversation'];
    if (!is_array($conversation))
    {
        $tmp = $conversation;
        $conversation = array($tmp);
    }
    $show = "";
    $user_name = $_SESSION['convoArr']['conversation']['user_name'];
    $bot_name = $_SESSION['convoArr']['conversation']['bot_name'];

    foreach ($conversation as $index => $conversation_subarray)
    {
        $show .= "<div class=\"usersay\">$user_name: " . stripslashes($conversation_subarray['input']) . "</div>";
        $show .= "<div class=\"botsay\">$bot_name: " . stripslashes($conversation_subarray['response']) . "</div>";
    }
    $_SESSION['convoArr']['send_to_user'] = $show;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning HTML", 4);

    
}

/**
 * function get_json()
 * This function formats the response as json
 * @link http://blog.program-o.com/?p=1229

 * @param  array $conversation - the conversation lines to format
 * @return void
 **/
function get_json($conversation)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Outputting response as JSON', 2);
    $show_json = array();
    $i = 0;

    foreach ($conversation as $index => $conversation_subarray)
    {
        $show_json['convo_id'] = $_SESSION['convoArr']['conversation']['convo_id'];
        $show_json['usersay'] = stripslashes($conversation_subarray['input']);
        $show_json['botsay'] = stripslashes($conversation_subarray['response']);
        $show_json['botData'] = $_SESSION['convoArr']['conversation'];
        $i++;
    }

    $_SESSION['convoArr']['send_to_user'] = json_encode($show_json);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning JSON string: " . $_SESSION['convoArr']['send_to_user'], 4);

    
}

/**
 * function get_xml()
 * This function formats the response as xml
 * @link http://blog.program-o.com/?p=1238

 * @param  array $conversation - the conversation lines to format
 * @return void
 **/
function get_xml($conversation)
{
    $addTags = array('bot_id', 'bot_name', 'user_id', 'user_name');
    $program_o = new SimpleXMLElement('<program_o/>');
    $program_o->addChild('version', VERSION);
    $program_o->addChild('status');
    $status = $program_o->status;
    $status->addChild('success', true);

    foreach ($addTags as $tag_name)
    {
        $tmpVal = $_SESSION['convoArr']['conversation'][$tag_name];
        $program_o->addChild($tag_name, $tmpVal);
    }

    $program_o->addChild('chat');
    $chat = $program_o->chat;

    foreach ($conversation as $index => $conversation_subarray)
    {
        if (empty($conversation_subarray)) {
            continue;
        }

        $line = $chat->addChild('line');
        $line->addChild('input', $conversation_subarray['input']);
        $line->addChild('response', $conversation_subarray['response']);
    }

    $responseXML = $program_o->asXML();
    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = true;
    $dom->loadXML($responseXML);

    $send_to_user = $dom->saveXML();
    $_SESSION['convoArr']['send_to_user'] = $send_to_user;

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning XML", 4);

    
}

/**
 * function display_conversation()
 * Displays the output of the conversation if the current format is XML or JSON and updated referenced $display if html
 *
 * @link  http://blog.program-o.com/?p=1233
 * @return void (void)
 */
function display_conversation()
{
    $display = $_SESSION['convoArr']['send_to_user'];
    $format = (isset($_SESSION['convoArr']['conversation']['format'])) ? _strtolower(trim($_SESSION['convoArr']['conversation']['format'])) : 'html';

    switch ($format)
    {
        case 'html' :
        case 'HTML' :
            $display = str_ireplace('<![CDATA[', '', $display);
            $display = str_replace(']]>', '', $display);
            break;
        case 'xml' :
        case 'XML' :
            header("Content-type: text/xml; charset=utf-8", false);
            header("Access-Control-Allow-Origin: *", false);
            echo trim($display);
            break;
        case 'json' :
        case 'JSON' :
            header("Content-type: text/plain; charset=utf-8", false);
            header("Access-Control-Allow-Origin: *", false);
            echo trim($display);
            break;
        default :
    }
}