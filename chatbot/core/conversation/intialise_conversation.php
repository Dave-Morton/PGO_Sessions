<?php

/***************************************
 * www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: chatbot/core/conversation/intialise_conversation.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: this file contains the functions intialise
 *          the conversation
 ***************************************/

/**
 * function intialise_convoArray()
 * A function to intialise the conversation array
 * This is the array that is built throught the conversation
 * @link http://blog.program-o.com/?p=1242
 * @return (void)
 **/
function intialise_convoArray()
{
    if (!isset($_SESSION['convoArr']['conversation']))
    {
        $_SESSION['convoArr']['conversation'] = array();
    }
    //set the initial convoArr values
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Intialising conversation", 4);
    //load blank topics
    load_blank_array_element('topic', "");
    //load blank thats
    load_blank_array_element('that', "");
    //load blank stars
    load_blank_array_element('star', "");
    //load blank stars
    load_blank_array_element('input', "");
    //load blank stack
    load_blank_stack();
    //load bot properties
    load_default_bot_values();
    //load the new client defaults
    load_new_client_defaults();
}

/**
 * function load_blank_array_element()
 * A function to intialise the conversation array values
 *
 * @link http://blog.program-o.com/?p=1244
 * @param  string $arrayIndex - the array element we are going to intialise
 * @param  string $defaultValue - the value which will be used to set the element
 * @return (void)
 */
function load_blank_array_element($arrayIndex, $defaultValue)
{
    global $remember_up_to;

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading blank $arrayIndex array", 4);
    //set in global config file
    $remember_up_to = (isset($_SESSION['convoArr']['conversation']['remember_up_to'])) ? $_SESSION['convoArr']['conversation']['remember_up_to'] : $remember_up_to;

    for ($i = 1; $i <= ($remember_up_to + 1); $i++)
    {
        $_SESSION['convoArr'][$arrayIndex][$i] = $defaultValue;
    }
}

/**
 * function load_blank_stack()
 * A function to intialise the conversation stack values
 *
 * @link     http://blog.program-o.com/?p=1246
 * @internal param string $arrayIndex - the array element we are going to intialise
 * @internal param string $defaultValue - the value which will be used to set the element
 * @return (void)
 */
function load_blank_stack()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading blank stack", 4);
    global $stack_value;
    //set in global config file
    $_SESSION['convoArr']['stack']['top'] = $stack_value;
    $_SESSION['convoArr']['stack']['second'] = $stack_value;
    $_SESSION['convoArr']['stack']['third'] = $stack_value;
    $_SESSION['convoArr']['stack']['fourth'] = $stack_value;
    $_SESSION['convoArr']['stack']['fifth'] = $stack_value;
    $_SESSION['convoArr']['stack']['sixth'] = $stack_value;
    $_SESSION['convoArr']['stack']['seventh'] = $stack_value;
    $_SESSION['convoArr']['stack']['last'] = $stack_value;
}

/**
 * function load_default_bot_values()
 * A function to intialise the chatbot properties
 *
 * @link http://blog.program-o.com/?p=1248
 * @return (void)
 */
function load_default_bot_values()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading db bot personality properties", 4);
    global $dbn, $bot_id;

    //set in global config file
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT * FROM `$dbn`.`botpersonality` WHERE `bot_id` = :bot_id;";
    $params = array(':bot_id' => $bot_id);
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "load db bot personality values SQL: $debugSQL", 3);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    foreach ($result as $row)
    {
        $_SESSION['convoArr']['bot_properties'][$row['name']] = $row['value'];
    }
}

/**
 * function add_new_conversation_vars()
 * A function add the new values from the user input into the conversation state
 *
 * @link http://blog.program-o.com/?p=1254
 * @param  string $say - the user input
 * @return (void)
 */
function add_new_conversation_vars($say)
{
    $rawSay = strip_tags($_SESSION['convoArr']['conversation']['rawSay']);
    $strippedSay = strip_tags($say);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "New conversation vars", 4);
    //put what the user has said on the front of the 'user_say' and 'input' subarray with a minimum clean to prevent injection
    push_on_front_convoArr("user_say", $strippedSay);
    $_SESSION['convoArr']['aiml']['user_raw'] = $rawSay;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "User RAW = {$_SESSION['convoArr']['aiml']['user_raw']}, RAW say = {$_SESSION['convoArr']['conversation']['rawSay']}");
    push_on_front_convoArr('input', $_SESSION['convoArr']['aiml']['user_raw']);
}

/**
 * function add_firstturn_conversation_vars()
 * A function add the bot values to the conversation state if this is the first turn
 *
 * @link http://blog.program-o.com/?p=1256
 * @return (void)
 */
function add_firstturn_conversation_vars()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "First turn", 4);

    if (!isset ($_SESSION['convoArr']['bot_properties']))
    {
        load_default_bot_values();
    }
}

/**
 * function push_on_front_convoArr()
 * A function to push items on the front of a subarray in convoArr
 *
 * @link http://blog.program-o.com/?p=1258
 * @param  string $arrayIndex - the subarray index to push to
 * @param  string $value - the value to push on teh subarray
 * @return (void)
 */
function push_on_front_convoArr($arrayIndex, $value)
{
    global $rememLimit, $remember_up_to;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Pushing '$value' to the front of the [$arrayIndex] array", 2);
    $remember_up_to = (isset($_SESSION['convoArr']['conversation']['remember_up_to'])) ? $_SESSION['convoArr']['conversation']['remember_up_to'] : $remember_up_to;
    //these subarray indexes are 2d
    $two_d_arrays = array("that", "that_raw");
    $arrayIndex = trim($arrayIndex);
    //mini clean
    $value = trim($value);
    $value = preg_replace('/\s\s+/', ' ', $value);
    $value = preg_replace('/\s\./', '.', $value);

    //there is a chance the subarray has not been set yet so check and if not set here
    if (!isset ($_SESSION['convoArr'][$arrayIndex][1]))
    {
        $_SESSION['convoArr'][$arrayIndex] = array();
        load_blank_array_element($arrayIndex, "");
    }

    //if the subarray is itself an array check it here
    if (in_array($arrayIndex, $two_d_arrays))
    {
        $matches = preg_match_all("# ?(([^\.\?!]*)+(?:[\.\?!]|(?:<br ?/?>))*)#ui", $value, $sentences);
        $cmatch = 0;

        //do another check to make sure the array is not just full of blanks
        foreach ($sentences as $temp)
        {
            foreach ($temp as $chk)
            {
                if (trim($chk) != "") {
                    $cmatch++;
                }
            }
        }

        //if there definately is something in the sentence array build the temp sentence array
        if (($cmatch > 0) && ($matches !== FALSE))
        {
            foreach ($sentences[1] as $index => $value)
            {
                if ($arrayIndex == "that")
                {
                    $t = ($value != '') ? clean_that($value, __FILE__, __FUNCTION__, __LINE__) : '';

                    if ($t != "") {
                        $tmp_sentence[] = $t;
                    }
                }
                else {
                    $tmp_sentence[] = $value;
                }
            }
            //reverse the array and store
            $sentences = array();
            $sentences = array_reverse($tmp_sentence);
        }
        else
        {
            $sentences = array();

            if ($arrayIndex == "that")
            {
                $sentences[0] = clean_that($value, __FILE__, __FUNCTION__, __LINE__);
            }
            else {
                $sentences[0] = $value;
            }
        }
        //make a space so that [0] is null (in accordance with the AIML array offset)
        array_unshift($sentences, NULL);
        unset ($sentences[0]);

        //push this onto the subarray and then clear [0] element (in accordance with the AIML array offset)
        array_unshift($_SESSION['convoArr'][$arrayIndex], $sentences);
        array_unshift($_SESSION['convoArr'][$arrayIndex], null);
        unset ($_SESSION['convoArr'][$arrayIndex][0]);
    }
    else
    {
        array_unshift($_SESSION['convoArr'][$arrayIndex], $value);
        array_unshift($_SESSION['convoArr'][$arrayIndex], NULL);
    }

    if ((trim($arrayIndex) == 'star') || (trim($arrayIndex) == 'topic'))
    {
        //keep 5 times as many topics and stars as lines of conversation
        $rememLimit_tmp = $rememLimit;
    }
    else {
        $rememLimit_tmp = $remember_up_to;
    }

    for ($i = $rememLimit_tmp + 1; $i <= count($_SESSION['convoArr'][$arrayIndex]); $i++)
    {
        if (isset ($_SESSION['convoArr'][$arrayIndex][$i])) {
            unset ($_SESSION['convoArr'][$arrayIndex][$i]);
        }
    }

    unset ($_SESSION['convoArr'][$arrayIndex][0]);

    if ($arrayIndex == "topic")
    {
        push_stack($value);
    }
}

/**
 * function load_bot_config()
 * A function to get the bot/convo configuration values out of the database
 *
 * @return (void)
 */
function load_bot_config()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading config data for the current bot.', 2);
    global $dbn, $format, $pattern, $conversation_lines, $remember_up_to, $debugemail, $debug_level, $debug_mode, $save_state, $error_response;

    //get the values from the db
    $bot_id = $_SESSION['convoArr']['conversation']['bot_id'];
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT * FROM `$dbn`.`bots` WHERE bot_id = :bot_id;";
    $params = array(':bot_id' => $bot_id);
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "load bot config SQL: $debugSQL", 3);
    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if (count($row) > 0)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading bot details from the database.', 4);
        $_SESSION['convoArr']['conversation']['conversation_lines'] = $row['conversation_lines'];
        $_SESSION['convoArr']['conversation']['remember_up_to'] = $row['remember_up_to'];
        $_SESSION['convoArr']['conversation']['debugemail'] = $row['debugemail'];
        $_SESSION['convoArr']['conversation']['debug_level'] = $row['debugshow'];
        $_SESSION['convoArr']['conversation']['debugmode'] = $row['debugmode'];
        $_SESSION['convoArr']['conversation']['save_state'] = $row['save_state'];
        $_SESSION['convoArr']['conversation']['default_aiml_pattern'] = $row['default_aiml_pattern'];
        $_SESSION['convoArr']['conversation']['bot_parent_id'] = $row['bot_parent_id'];
        $error_response = (!empty($row['error_response'])) ? $row['error_response'] : $error_response;
    }
    else
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Unable to load bot details from the database. Loading default values.', 4);
        $_SESSION['convoArr']['conversation']['conversation_lines'] = $conversation_lines;
        $_SESSION['convoArr']['conversation']['remember_up_to'] = $remember_up_to;
        $_SESSION['convoArr']['conversation']['debugemail'] = $debugemail;
        $_SESSION['convoArr']['conversation']['debug_level'] = $debug_level;
        $_SESSION['convoArr']['conversation']['debugmode'] = $debug_mode;
        $_SESSION['convoArr']['conversation']['save_state'] = $save_state;
        $_SESSION['convoArr']['conversation']['default_aiml_pattern'] = $pattern;
        $_SESSION['convoArr']['conversation']['bot_parent_id'] = 0;
    }

    //if return format is not html overide the debug type
    if ($_SESSION['convoArr']['conversation']['format'] != "html")
    {
        $_SESSION['convoArr']['conversation']['debugmode'] = 1;
    }
}

/**
 * function log_conversation(()
 * A function to log the conversation
 *
 * @link http://blog.program-o.com/?p=1262
 * @return (void)
 */
function log_conversation()
{
    //db globals
    global $dbn;
    //save_file(_LOG_PATH_ . 'conversation.data.txt', print_r($_SESSION['convoArr']['conversation'], true));
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Saving the conversation to the DB.', 2);
    //clean and set
    $usersay = $_SESSION['convoArr']['aiml']['user_raw'];
    $usersay = $_SESSION['convoArr']['conversation']['rawSay'];
    $usersay = str_replace("'", "\'", $usersay);
    $botsay = $_SESSION['convoArr']['aiml']['parsed_template'];
    $botsay = str_replace("'", "\'", $botsay);
    $user_id = $_SESSION['convoArr']['conversation']['user_id'];
    $convo_id = $_SESSION['convoArr']['conversation']['convo_id'];
    $bot_id = $_SESSION['convoArr']['conversation']['bot_id'];

    /** @noinspection SqlDialectInspection */
    $sql = "INSERT INTO `$dbn`.`conversation_log` (
      `id` ,
      `input` ,
      `response` ,
      `user_id` ,
      `convo_id` ,
      `bot_id` ,
      `timestamp`
    )
    VALUES (
      NULL ,
      :usersay,
      :botsay,
      :user_id,
      :convo_id,
      :bot_id,
      CURRENT_TIMESTAMP
    )";

    $params = array(
        ':usersay'  => $usersay,
        ':botsay'   => $botsay,
        ':user_id'  => $user_id,
        ':convo_id' => $convo_id,
        ':bot_id'   => $bot_id,
    );
    $displaySQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Saving conservation SQL: $displaySQL", 3);

    $numRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

    // check to see if there is already a setting in the table

    $insertSQL = <<<endSQL
insert into `client_properties`
    (`id`, `user_id`, `bot_id`, `name`, `value`)
    values(null, :user_id, :bot_id, :name, :value);
endSQL;
    $updateSQL = 'update `client_properties` set value = :value where `bot_id` = :bot_id and `user_id` = :user_id and `name` = :name;';
    $client_properties = $_SESSION['convoArr']['client_properties'];
    $insertParams = [];
    $updateParams = [];
    foreach ($client_properties as $name => $value)
    {
        $params = array(
            ':bot_id' => $bot_id,
            ':user_id' => $user_id,
            ':name' => $name,
            ':value' => $value,
        );
        $lookSQL = 'select id from client_properties where `bot_id` = :bot_id and `user_id` = :user_id and `name` = :name;';
        $lookParams = $params;
        unset($lookParams[':value']);
        $result = "foo!";
        $result = db_fetch($lookSQL, $lookParams, __FILE__, __FUNCTION__, __LINE__);
        $debug_SQL = db_parseSQL($lookSQL, $lookParams);
        if (!empty($result))
        {
            $updateParams[] = $params;
        }
        else $insertParams[] = $params;
    }
    $insertSuccess = (!empty($insertParams)) ? db_write($insertSQL, $insertParams, true, __FILE__, __FUNCTION__, __LINE__) : true;
    $updateSuccess = (!empty($updateParams)) ? db_write($updateSQL, $updateParams, true, __FILE__, __FUNCTION__, __LINE__) : true;
}

/**
 * function log_conversation_state(()
 * A function to log the conversation state
 *
 * @link http://blog.program-o.com/?p=1264
 * @return (void)
 */
function log_conversation_state()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Logging the state of the conversation.', 2);
    global $dbn, $user_name;
    //get undefined defaults from the db
    runDebug(__FILE__, __FUNCTION__, __LINE__, "logging state", 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "user name = $user_name. Stored user name = " . $_SESSION['convoArr']['conversation']['user_name'], 4);

    $serialise_convo = serialize(reduceConvoArr());
    $user_id = $_SESSION['convoArr']['conversation']['user_id'];
    $sql_addon = (!empty ($user_name)) ? "`user_name` = '" . $user_name . "', " : '';

    /** @noinspection SqlDialectInspection */
    $sql = "UPDATE `$dbn`.`users`
                SET
                `state` = '$serialise_convo',
                `last_update` = NOW(),
                $sql_addon
                `chatlines` = `chatlines`+1
                WHERE `id` = '$user_id' LIMIT 1";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "updating conversation state SQL: $sql", 3);

    $numRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

    // Save client_properties to DB
    $bot_id = $_SESSION['convoArr']['conversation']['bot_id'];
    $cpSQL = <<<endSQL
insert into `client_properties`
    (`id`, `user_id`, `bot_id`, `name`, `value`)
    values(null, :user_id, :bot_id, :name, :value)
    on duplicate key update value=:value;
endSQL;
    $client_properties = $_SESSION['convoArr']['client_properties'];
    $params = [];
    foreach ($client_properties as $key => $value)
    {
        $params[] = array(
            ':bot_id' => $bot_id,
            ':user_id' => $user_id,
            ':name' => $key,
            ':value' => $value,
        );
    }
    $success = db_write($cpSQL, $params, true, __FILE__, __FUNCTION__, __LINE__);
}

/**
 * function get_conversation_state(()
 * A function to get the conversation state from the db
 *
 * @link http://blog.program-o.com/?p=1266
 * @return (void)
 */
function get_conversation_state()
{
    global $dbn, $unknown_user;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "getting state", 4);
    $user_id = $_SESSION['convoArr']['conversation']['user_id'];

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT * FROM `$dbn`.`users` WHERE `id` = :user_id LIMIT 1";
    $params = array(':user_id' => $user_id);
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Getting conversation state SQL: $debugSQL", 3);
    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if (($row) && (count($row) > 0))
    {
        unserialize($row['state']);
        $user_name = (!empty ($row['user_name'])) ? $row['user_name'] : $unknown_user;
        $_SESSION['convoArr']['conversation']['user_name'] = $user_name;
        $_SESSION['convoArr']['client_properties']['name'] = $user_name;
    }

}

/**
 * function check_set_bot(()
 * A function to check and set the bot id, name and default format for bot
 *
 * @link http://blog.program-o.com/?p=1269

 * @return (void)
 */
function check_set_bot()
{
    global $form_vars, $error_response, $dbn, $bot_id, $format, $unknown_user;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking and/or setting the current bot.', 2);

    //check to see if bot_id has been passed if not load default
    if ((isset ($form_vars['bot_id'])) && (trim($form_vars['bot_id']) != ""))
    {
        $bot_id = trim($form_vars['bot_id']);
    }
    elseif (isset ($_SESSION['convoArr']['conversation']['bot_id']))
    {
        $bot_id = $_SESSION['convoArr']['conversation']['bot_id'];
    }
    // else $bot_id is unchanged

    //get the values from the db
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT * FROM `$dbn`.`bots` WHERE bot_id = :bot_id AND `bot_active` = 1;";
    $params = array(':bot_id' => $bot_id);
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Making sure the bot exists. SQL = $debugSQL", 3);
    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if (($row) && (count($row) > 0))
    {
        $error_response = (!empty($row['error_response'])) ? $row['error_response'] : $error_response;
        $unknown_user = $row['unknown_user'];

        if ($unknown_user === '[IP]')
        {
            $unknown_user = $_SERVER['REMOTE_ADDR'];
        }

        $_SESSION['convoArr']['conversation']['bot_name'] = $row['bot_name'];
        $_SESSION['convoArr']['conversation']['bot_id'] = $bot_id;
        $_SESSION['convoArr']['conversation']['format'] = $row['format'];
        $_SESSION['convoArr']['conversation']['unknown_user'] = $unknown_user;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "BOT ID: $bot_id", 2);
    }
    else
    {
        $_SESSION['convoArr']['conversation']['format'] = $format;
        $_SESSION['convoArr']['conversation']['bot_id'] = $bot_id;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "ERROR - Cannot find bot id: $bot_id", 1);
    }
}

/**
 * function check_set_convo_id(()
 * A function to check and set the convo id
 *
 * @link http://blog.program-o.com/?p=1276
 * @return (void)
 */
function check_set_convo_id()
{
    global $form_vars;
    //check to see if convo_id has been passed if not load default
    if (isset($form_vars['convo_id']))
    {
        $convo_id = $form_vars['convo_id'];
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Obtaining the convo id from form vars. Value: $convo_id", 4);
    }
    elseif (isset ($_SESSION['convoArr']['conversation']['convo_id']))
    {
        $convo_id = $_SESSION['convoArr']['conversation']['convo_id'];
        runDebug(__FILE__, __FUNCTION__, __LINE__, "CONVO ID already exists. Value: $convo_id", 2);
    }
    else
    {
        $convo_id = session_id();
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Cannot find CONVO ID. Using default: $convo_id", 1);
    }
    $_SESSION['convoArr']['conversation']['convo_id'] = $convo_id;

}

/**
 * function check_set_user(()
 * A function to check and set the user's information
 *
 * @link http://blog.program-o.com/?p=1278
 * @return (void)
 */
function check_set_user()
{
    global $dbn, $unknown_user, $bot_id;
    $chatlines = 0;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking and setting the user info, as needed.', 2);

    // initial setup
    $convo_id = (isset ($_SESSION['convoArr']['conversation']['convo_id'])) ? $_SESSION['convoArr']['conversation']['convo_id'] : session_id();
    $ip = $_SERVER['REMOTE_ADDR'];
    $_SESSION['convoArr']['client_properties']['ip_address'] = $ip;


    // get initial user data
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `user_name`, `id`, `chatlines` FROM `$dbn`.`users` WHERE `session_id` = :convo_id limit 1;";
    $row = db_fetch($sql, array(':convo_id' => $convo_id), __FILE__, __FUNCTION__, __LINE__);

    if ($row === false)
    {
        intisaliseUser();
        $user_id = $_SESSION['convoArr']['conversation']['user_id'];
        $user_name = $unknown_user;
    }
    else
    {
        $user_id = $row['id'];
        $user_name = (!empty ($row['user_name'])) ? $row['user_name'] : $unknown_user;
        $chatlines = (!empty ($row['chatlines'])) ? $row['chatlines'] : 0;
    }

    // get client properties for the user
    $sql = "SELECT `name`, `value` FROM `$dbn`.`client_properties` WHERE `user_id` = :user_id AND `bot_id` = :bot_id;";
    $params = array(
        ':bot_id' => $bot_id,
        ':user_id' => $user_id,
    );
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading client properties from the DB - sql:\n$debugSQL", 3);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    foreach ($result as $row)
    {
        extract($row);
        $_SESSION['convoArr']['client_properties'][$name] = $value;
    }

    $_SESSION['convoArr']['client_properties']['name'] = $user_name;
    $_SESSION['convoArr']['conversation']['user_name'] = $user_name;
    $_SESSION['convoArr']['conversation']['user_id'] = $user_id;
    $_SESSION['convoArr']['conversation']['totallines'] = $chatlines;

}

/**
 * function check_set_format(()
 * A function to check and set the conversation output type
 *
 * @link http://blog.program-o.com/?p=1281
 * @return (void)
 */
function check_set_format()
{
    global $format, $form_vars;
    $formatsArr = array('html', 'xml', 'json');

    if ((isset ($form_vars['format'])) && (trim($form_vars['format']) != ""))
    {
        $desired_format = strtolower(trim($form_vars['format']));
    }
    else {
        $desired_format = $format;
    }

    if (!in_array($format, $formatsArr))
    {
        $_SESSION['convoArr']['conversation']['format'] = $format; // default format
        $_SESSION['convoArr']['debug']['intialisation_error'] = "Incompatible return type: $format";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "ERROR - bad return type: $format", 1);
    }
    else
    {
        //at this point we can overwrite the conversation format.
        $_SESSION['convoArr']['conversation']['format'] = $desired_format;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Using format: $format", 4);
    }

}


/**
 * function load_that(()
 * A function to load the previous bot responses into the convoArr['that'] array
 *
 * @link http://blog.program-o.com/?p=1283
 * @return (void)
 */
function load_that()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading the THAT array.', 2);
    global $dbn, $remember_up_to, $bot_id;

    $remember_up_to = (!empty ($_SESSION['convoArr']['conversation']['remember_up_to'])) ? $_SESSION['convoArr']['conversation']['remember_up_to'] : $remember_up_to;
    $user_id = $_SESSION['convoArr']['conversation']['user_id'];
    $bot_id = (!empty($_SESSION['convoArr']['conversation']['bot_id'])) ? $_SESSION['convoArr']['conversation']['bot_id'] : $bot_id;
    $limit = $remember_up_to;

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `input`, `response` FROM `$dbn`.`conversation_log` WHERE `user_id` = :user_id AND `bot_id` = :bot_id ORDER BY `id` DESC limit $limit;"; // desc
    $params = array(
        ':bot_id' => $bot_id,
        ':user_id' => $user_id
    );
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Getting conversation log entries for the current user. SQL:\n$debugSQL", 3);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if ($result)
    {
        $tmpThatRows = array();
        $tmpRawThatRows = array();
        $tmpInputRows = array();
        $tmpThat = array();
        $tmpRawThat = array();
        $tmpInput = array();
        $puncuation = array(',', '?', ';', '!');

        foreach ($result as $row)
        {
            $tmpRawThatRows[] = $row['response'];
            $tmpThatRows[] = $row['response'];
            $tmpInputRows[] = $row['input'];
        }

        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Inputs returned:' . print_r($tmpInputRows, true), 1);
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading previous responses into the ~THAT~ array.', 4);
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Responses returned:' . print_r($tmpThatRows, true), 1);
        $tmpThatRows = array_reverse($tmpThatRows);

        foreach ($tmpThatRows as $row)
        {
            $row = str_replace($puncuation, '.', $row);
            $tmpThat[] = explode('.', $row);
        }

        array_unshift($tmpThat, NULL);
        unset ($tmpThat[0]);

        foreach ($tmpRawThatRows as $row)
        {
            $tmpRawThat[] = explode('.', $row);
        }

        array_unshift($tmpRawThat, NULL);
        unset ($tmpRawThat[0]);
        $_SESSION['convoArr']['raw_that'] = $tmpRawThat;

        foreach ($tmpThat as $index => $value)
        {
            $value = implode_recursive(' ', $value, __FILE__, __FUNCTION__, __LINE__);
            $value = clean_that($value, __FILE__, __FUNCTION__, __LINE__);
            push_on_front_convoArr('that', $value);
        }


        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading previous user inputs into the ~INPUT~ array.', 4);
        $tmpInputRows = array_reverse($tmpInputRows);

        foreach ($tmpInputRows as $row)
        {
            $row = str_replace($puncuation, '.', $row);
            $tmpInput[] = explode('.', $row);
        }

        array_unshift($tmpThat, NULL);
        unset ($tmpThat[0]);


        foreach ($tmpInput as $index => $value)
        {
            $value = implode_recursive(' ', $value, __FILE__, __FUNCTION__, __LINE__);
            $value = clean_that($value, __FILE__, __FUNCTION__, __LINE__);
            push_on_front_convoArr('input', $value);
        }
    }
    else {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Couldn\'t find any previous inputs or responses.', 4);
    }
}

