<?php
require_once "./config.php";

function get_conversation_history()
{
    $conversation_history = array();
    if (file_exists(CHAT_FILE)) {
        $chat_lines = explode("----------------------------------------\n", file_get_contents(CHAT_FILE));
        $chat_lines = array_filter(array_map('trim', $chat_lines));
        $num_chat_lines = count($chat_lines);
        $num_history_lines = min($num_chat_lines, 5);
        $conversation_history = array_slice($chat_lines, -$num_history_lines);
    } else {
        $conversation_history = array();
    }
    return $conversation_history;
}

function generate_npc_prompt($location, $npc_bios)
{
    $conversation_history = get_conversation_history();
    $npc_prompt = "Please generate natural and engaging responses for the NPCs that continue the conversation. Not all NPCs need to respond every time, and their responses should be meaningful. Separate each conversational, action, inner monolog, and emotional response onto its own line, and avoid mixing response types.'\n\n";
    $npc_prompt .= "Here is the location and setting: $location\n\n";
    $npc_prompt .= "\nConversation History:\n" . implode("\n", $conversation_history) . "\n\n";
    $npc_prompt .= "Here are the bios for the NPCs, you may only create responses for these NPCs! You may not speak as anything else! Especially as 'You:':\n";
    foreach ($npc_bios as $name => $bio) {
        $npc_prompt .= "$name: $bio\n";
    }

    // send prompt to API
    global $log_file, $api_key;
    $data = [
        'model' => 'gpt-3.5-turbo',
        'temperature' => 0.5,
        'max_tokens' => 256,
        'top_p' => 1,
        'frequency_penalty' => 0.5,
        'presence_penalty' => 0.2,
        'messages' => [
            ['role' => 'system', 'content' => 'You are currently controlling several NPCs in a conversational simulation with an uncontrolled human. Your task is to generate responses for the NPCs in a natural and engaging way. The NPCs can have conversational, action, inner monolog, and emotional responses. Use the character name before each conversational response. Use ** before and after action, inner monolog, and emotional responses. Avoid mixing response types on the same line. Each response should be complete, and you should plan ahead for the total length of your response. NPCs should only respond when there is a reason to do so. Remember that you are only speaking as the NPCs, and not as the human "The human will have there responses in the conversation history as You: RESPONCE". As far as the NPCs are concern it is a voice they can all here that comes from everywhere, they do not see the human. As for the narrator "The narrator will have there responses in the conversation history as NARRATOR: RESPONCE" do not speak as the narrator unless prompted to do so. '],
            ['role' => 'user', 'content' => $npc_prompt]
        ]
    ];

    $response = send_prompt_to_api($data, $api_key, $log_file);
    return $response;
}

function generate_narrator_prompt()
{
    $conversation_history = get_conversation_history();
    $narrator_prompt = "You will now narrate this conversational simulation. Reply to this with a response NARRATOR: followed by one to three paragraph narration of actions, feelings, setting, location, events etc.. based on the context of the conversation so far:\n\n";
    $narrator_prompt .= "Conversation History:\n" . implode("\n", $conversation_history) . "\n\n";

    // send prompt to API
    global $log_file, $api_key;
    $data = [
        'model' => 'gpt-3.5-turbo',
        'temperature' => 0.5,
        'max_tokens' => 1024,
        'top_p' => 1,
        'frequency_penalty' => 0.5,
        'presence_penalty' => 0.2,
        'messages' => [
            ['role' => 'system', 'content' => 'You are currently running a conversational simulation between several NPCs that you control, a Narrator that you control and a human that you do not control. Generate a response for the Narrator that helps continues the conversation in a natural and engaging way. You may only speak for the narrator (NARRATOR:), and your responce should start with NARRATOR: followed by one to three paragraph narration of actions, feelings, setting, location, events etc..'],
            ['role' => 'user', 'content' => $narrator_prompt]
        ]
    ];

    $response = send_prompt_to_api($data, $api_key, $log_file);
    return $response;
}

function send_prompt_to_api($data, $api_key, $log_file)
{
    // create log entry for request
    $request_log = date('Y-m-d H:i:s') . " - Request: " . json_encode($data) . "\n";
    file_put_contents($log_file, $request_log, FILE_APPEND | LOCK_EX);

    // send prompts to Chat GPT API
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ));
    $response = curl_exec($curl);
    curl_close($curl);

    // create log entry for response
    $response_log = date('Y-m-d H:i:s') . " - Response: " . $response . "\n";
    file_put_contents($log_file, $response_log, FILE_APPEND | LOCK_EX);

    // parse API response
    $response = json_decode($response, true);

    if (isset($response['choices'][0]['message']['content'])) {
        $text = $response['choices'][0]['message']['content'];
        // Append response to chat log
        $chat_line = '';
        $action_npc_name = ''; // initialize the variable to store the name of the NPC who performed the action
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $npc_response = '';
            if (strpos($line, ':') !== false) {
                // NPC response
                list($npc_name, $npc_response) = explode(':', $line, 2);
                $npc_name = trim($npc_name);
                $npc_response = trim($npc_response);
                $chat_line .= "$npc_name: $npc_response\n";
                $action_npc_name = $npc_name; // update the NPC name variable for the next action
            } elseif (strpos($line, '**') !== false) {
                // Action
                $action = str_replace('**', '', $line);
                $action = trim($action);
                $chat_line .= "**$action**\n"; // use the name of the NPC who performed the action
                $action_npc_name = ''; // reset the NPC name variable for conversational responses
            }
        }
        $chat_line .= "----------------------------------------\n\n";

        // Append new chat line to chat file
        $result = file_put_contents(CHAT_FILE, $chat_line, FILE_APPEND | LOCK_EX);
        return $text;
    } else {
        $text = '';
        return $text;
    }
}
