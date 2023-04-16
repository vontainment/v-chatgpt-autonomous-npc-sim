<?php
require_once "./config.php";

function read_npc_files()
{
    $npc_files = glob(NPC_DIR . '*');
    $npc_bios = array();
    foreach ($npc_files as $file) {
        $name = str_replace('_', ' ', basename($file));
        $bio = file_get_contents($file);
        $npc_bios[$name] = $bio;
    }
    return $npc_bios;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['human_response'])) {
        $human_response = $_POST['human_response'];
        $chat_log = "You: $human_response\n----------------------------------------\n";
        file_put_contents(CHAT_FILE, $chat_log, FILE_APPEND);
        $location = file_get_contents(LOCATION_FILE);
        $npc_bios = read_npc_files();
        generate_npc_prompt($location, $npc_bios);
    } elseif (isset($_POST['generate_npc_prompt'])) {
        $npc_bios = read_npc_files();
        $location = file_get_contents(LOCATION_FILE);
        generate_npc_prompt($location, $npc_bios);
    } elseif (isset($_POST['generate_narrator_prompt'])) {
        generate_narrator_prompt();
    } elseif (isset($_POST['refresh_chat_log'])) {
        $chat_log = file_exists(CHAT_FILE) ? file_get_contents(CHAT_FILE) : '';
        echo nl2br($chat_log);
        exit();
    }
}
