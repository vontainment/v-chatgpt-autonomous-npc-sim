<?php
require_once "./config.php";
$chat_log = file_exists(CHAT_FILE) ? file_get_contents(CHAT_FILE) : '';
echo nl2br($chat_log);
