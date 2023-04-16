<?php
require_once "./config.php";
require_once "./api.php";
require_once "./cron.php";
?>

<!DOCTYPE html>
<html>

<head>
    <title>Conversational Simulation</title>
    <link rel="stylesheet" href="./style.css">
</head>

<body>
    <div class="container">
        <h1>Autonomous NPC Simulator</h1>
        <div id="chat-log">
            <?php include_once "./chatlog.php"; ?>
        </div>
        <div class="flex">
            <form method="POST" id="human-form" action="index.php">
                <textarea name="human_response" id="human-response" rows="4" cols="50"></textarea>
                <button type="submit" id="submit-btn">Submit</button>
            </form>
            <button type="submit" name="run_simulation" id="generate-npc-prompt-btn">Run Simulation</button>
        </div>
    </div>
    <script src="./scripts.js"></script>
</body>

</html>