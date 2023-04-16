function refreshChatLog() {
  fetch("./chatlog.php")
    .then((response) => response.text())
    .then((data) => {
      var chatLogDiv = document.getElementById("chat-log");
      chatLogDiv.innerHTML = data;
      chatLogDiv.scrollTop = chatLogDiv.scrollHeight; // scroll to bottom
    })
    .catch((error) => console.error(error));
}

var chatLogDiv = document.getElementById("chat-log");
chatLogDiv.scrollTop = chatLogDiv.scrollHeight; // scroll to bottom

function generateNPCPrompt() {
  console.log("Generating NPC prompt...");
  var formData = new FormData();
  formData.append("generate_npc_prompt", "true");
  fetch("./index.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (response.ok) {
        console.log("NPC prompt generated");
        refreshChatLog(); // Call refreshChatLog after generating NPC prompt
      } else {
        throw new Error("Network response was not ok");
      }
    })
    .catch((error) => console.error("Error generating NPC prompt:", error));
  console.log("generateNPCPrompt() called");
}

document
  .getElementById("generate-npc-prompt-btn")
  .addEventListener("click", generateNPCPrompt);

function generateNarratorPrompt() {
  console.log("Generating narrator prompt...");
  var formData = new FormData();
  formData.append("generate_narrator_prompt", "true");
  fetch("./index.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (response.ok) {
        console.log("Narrator prompt generated");
        refreshChatLog(); // Call refreshChatLog after generating narrator prompt
      } else {
        throw new Error("Network response was not ok");
      }
    })
    .catch((error) =>
      console.error("Error generating narrator prompt:", error)
    );
  console.log("generateNarratorPrompt() called");
}

//document
//  .getElementById("generate-narrator-prompt-btn")
//  .addEventListener("click", generateNarratorPrompt);

setInterval(generateNPCPrompt, 120000);
setInterval(generateNarratorPrompt, 600000);
