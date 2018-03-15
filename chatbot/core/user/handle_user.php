<?php
/***************************************
 * www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: chatbot/core/user/handle_user.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: this file contains the functions to handle the
 *          user in the conversation
 ***************************************/

/**
 * function load_new_client_defaults()
 * A function to intialise clients values
 * @return void
 **/
function load_new_client_defaults()
{
    global $unknown_user;
    //to do could put this in an array
    //todo check this out
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading client defaults', 2);
    $_SESSION['convoArr']['client_properties']['name'] = $unknown_user;
    $_SESSION['convoArr']['client_properties']['id'] = session_id();
}

/**
 * function get_user_id()
 * A function to get the user id
 * @return void
 **/
function get_user_id()
{
    //db globals
    global $dbn, $unknown_user;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Getting user ID.', 2);

    //get undefined defaults from the db
    /** @noinspection SqlDialectInspection */
    $sql = 'SELECT * FROM `$dbn`.`users` WHERE `session_id` = :convo_id limit 1;';
    $params = array(':convo_id' => $_SESSION['convoArr']['conversation']['convo_id']);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $count = count($result);

    if ($count > 0)
    {
        $row = $result[0];
        $_SESSION['convoArr']['conversation']['user_id'] = $row['id'];
        // add user name, if set
        $_SESSION['convoArr']['conversation']['user_name'] = (!empty($_SESSION['convoArr']['client_properties']['name'])) ? $_SESSION['convoArr']['client_properties']['name'] : (!empty($row['user_name'])) ? $row['user_name'] : $unknown_user;
        $_SESSION['convoArr']['client_properties']['name'] = $_SESSION['convoArr']['conversation']['user_name'];
        $msg = "existing";
    }
    else
    {
        intisaliseUser();
        $msg = "new";
    }


    runDebug(__FILE__, __FUNCTION__, __LINE__, "Getting $msg user id:" . $_SESSION['convoArr']['conversation']['user_id'], 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "get_user_id SQL: $sql", 3);
}

/**
 * function intisaliseUser()
 * This function gets data such as the referer to store in the db
 *
 * @internal param string $convo_id - user session
 * @return int $user_id - the newly created user id
 */
function intisaliseUser()
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Initializing user.', 2);
    //db globals
    global $bot_id, $unknown_user;
    $convo_id = $_SESSION['convoArr']['conversation']['convo_id'];
    $username = !empty($_SESSION['convoArr']['conversation']['user_name']) ? $_SESSION['convoArr']['conversation']['user_name'] : $unknown_user;
    $sr = "";
    $sa = "";
    $sb = "unknown browser";

    if (isset($_SERVER['REMOTE_ADDR']))
    {
        $sa = $_SERVER['REMOTE_ADDR'];
    }

    if (isset($_SERVER['HTTP_REFERER']))
    {
        $sr = $_SERVER['HTTP_REFERER'];
    }

    if (isset($_SERVER['HTTP_USER_AGENT']))
    {
        $sb = $_SERVER['HTTP_USER_AGENT'];
    }
    /** @noinspection SqlDialectInspection */
    $sql = <<<endSQL
INSERT INTO `users`
        (`id`, `user_name`, `session_id`, `bot_id`, `chatlines`,`ip`, `referer`, `browser`, `date_logged_on` , `last_update`    , `state`)
 VALUES (NULL, :username  , :convo_id   , :bot_id , '0'        , :sa, :sr      , :sb      , CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ''     );
endSQL;

    $params = array(
        ':username' => $username,
        ':convo_id' => $convo_id,
        ':bot_id'   => $bot_id,
        ':sa'       => $sa,
        ':sr'       => $sr,
        ':sb'       => $sb,
    );

    $debugSQL = db_parseSQL($sql, $params);

    $numRows = db_write($sql, $params);

    $user_id = db_lastInsertId();
    $_SESSION['convoArr']['conversation']['user_id'] = $user_id;
    $_SESSION['convoArr']['conversation']['totallines'] = 0;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "intisaliseUser #$user_id SQL: $debugSQL", 3);


    //add the username to the client properties....
    /** @noinspection SqlDialectInspection */
    $sql = 'INSERT INTO `client_properties` (`id`,`user_id`,`bot_id`,`name`,`value`)
  VALUES (NULL, :user_id, :bot_id, \'name\', :username);';

    $params = array(
        ':user_id' => $user_id,
        ':bot_id'   => $bot_id,
        ':username' => $username,
    );
    $numRows = db_write($sql, $params);
}