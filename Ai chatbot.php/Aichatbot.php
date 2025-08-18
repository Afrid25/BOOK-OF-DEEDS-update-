<!-- Chat UI with quick replies, avatars, XP bar, language switcher, mood picker -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>UniAI Chatbot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    .typing-indicator { animation: blink 1s infinite; }
    @keyframes blink { 50% { opacity: 0.5; } }
    .high-contrast {
      background: #000 !important;
      color: #fff !important;
    }
  </style>
</head>
<body class="bg-gray-100">
  <div class="max-w-md mx-auto mt-4 bg-white rounded shadow-lg flex flex-col h-screen">
    <div class="p-4 border-b flex items-center justify-between">
      <span class="font-bold text-lg">👩‍🎓 UniAI</span>
      <div>
        <select id="lang" class="border rounded px-2 py-1">
          <option value="EN">English</option>
          <option value="BN">বাংলা</option>
        </select>
        <button id="contrast" class="ml-2 px-2 py-1 border rounded">🌓</button>
        <button id="accessBtn" class="ml-2 px-2 py-1 border rounded" aria-label="Accessibility Settings">♿</button>
      </div>
    </div>
    <div id="xp-bar" class="bg-blue-100 h-2"></div>
    <div id="chat" class="flex-1 overflow-y-auto p-4 space-y-2"></div>
    <div class="p-2 flex space-x-2">
      <input id="msg" class="flex-1 border rounded px-2 py-1" placeholder="Type your message...">
      <button id="send" class="bg-blue-500 text-white px-4 py-1 rounded">Send</button>
    </div>
    <div class="p-2 flex space-x-2">
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="planner">📅 Study Planner</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="pomodoro">⏰ Pomodoro</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="motivation">💡 Motivation</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="journal">📔 Journal</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="mentor">🧑‍🏫 Mentor</button>
    </div>
    <div class="p-2 flex space-x-2">
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="study_tip">📚 Get Study Tip</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="mentor">🧑‍🏫 Talk to Mentor</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="mood_check">😊 Mood Check</button>
      <button class="quick bg-gray-200 px-2 py-1 rounded" data-quick="routine">🗓️ Daily Routine</button>
    </div>
    <div class="p-2 flex items-center">
      <span>Mood:</span>
      <select id="mood" class="ml-2 border rounded px-2 py-1">
        <option value="">😐 Neutral</option>
        <option value="happy">😊 Happy</option>
        <option value="sad">😢 Sad</option>
        <option value="stressed">😰 Stressed</option>
        <option value="excited">🤩 Excited</option>
      </select>
    </div>
    <!-- Add this button near chat controls -->
    <button id="dashboardBtn" class="ml-2 px-2 py-1 border rounded">📊 Dashboard</button>
    <div id="dashboardModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-80">
        <h2 class="font-bold text-lg mb-2">Your Progress</h2>
        <div id="xpProgress"></div>
        <div id="moodTrend"></div>
        <div id="taskStats"></div>
        <button onclick="document.getElementById('dashboardModal').classList.add('hidden')" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded">Close</button>
      </div>
    </div>
    <button id="challengeBtn" class="ml-2 px-2 py-1 border rounded">🏆 Challenges</button>
    <div id="challengeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-80">
        <h2 class="font-bold text-lg mb-2">Your Challenges</h2>
        <div id="challengeList"></div>
        <button onclick="document.getElementById('challengeModal').classList.add('hidden')" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded">Close</button>
      </div>
    </div>
    <button id="peerBtn" class="ml-2 px-2 py-1 border rounded">💬 Talk to Peer</button>
    <button id="mindfulnessBtn" class="ml-2 px-2 py-1 border rounded">🧘 Mindfulness</button>
    <button id="helpBtn" class="ml-2 px-2 py-1 border rounded">🚨 Emergency Help</button>
    <input type="file" id="noteFile" class="ml-2 px-2 py-1 border rounded">
    <button id="uploadBtn" class="ml-2 px-2 py-1 border rounded">📄 Summarize Notes</button>
    <button id="privacyBtn" class="ml-2 px-2 py-1 border rounded">🔒 Privacy</button>
    <div id="privacyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-80">
        <h2 class="font-bold text-lg mb-2">Privacy Settings</h2>
        <label><input type="checkbox" id="privateMode"> Private Mode</label>
        <button onclick="document.getElementById('privacyModal').classList.add('hidden')" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded">Close</button>
      </div>
    </div>
    <button id="personaBtn" class="ml-2 px-2 py-1 border rounded">🛠️ Persona</button>
    <div id="personaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-80">
        <h2 class="font-bold text-lg mb-2">Mentor Persona</h2>
        <select id="personaType" class="w-full border rounded px-2 py-1">
          <option value="Mentor">Mentor</option>
          <option value="Motivator">Motivator</option>
          <option value="Listener">Listener</option>
          <option value="Friendly">Friendly</option>
          <option value="Strict">Strict</option>
          <option value="Humorous">Humorous</option>
        </select>
        <button onclick="document.getElementById('personaModal').classList.add('hidden')" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded">Close</button>
      </div>
    </div>
  </div>
  <script>
    let persona = 'Mentor', lang = 'EN', xp = 0;
    document.getElementById('lang').onchange = e => lang = e.target.value;
    document.querySelectorAll('.quick').forEach(btn => btn.onclick = () => sendMsg('', btn.dataset.quick));
    document.getElementById('send').onclick = () => sendMsg(document.getElementById('msg').value);
    document.getElementById('contrast').onclick = () => document.body.classList.toggle('bg-black');
    document.getElementById('accessBtn').onclick = () => {
      document.body.classList.toggle('high-contrast');
      document.getElementById('chat').style.fontSize = '20px';
    };
    function sendMsg(msg, quick='') {
      let mood = document.getElementById('mood').value;
      showMsg('user', msg || quick, persona);
      showTyping();
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: msg, persona, lang, quick, mood})
      })
      .then(r=>r.json()).then(data=>{
        hideTyping();
        showMsg('bot', data.reply, data.persona, data.timestamp);
        speak(data.reply);
        xp += data.xp;
        updateXPBar(xp);
      });
      document.getElementById('msg').value = '';
    }
    function showMsg(role, text, persona, ts='') {
      let chat = document.getElementById('chat');
      let avatar = role==='user' ? '🧑' : (persona==='Mentor'?'🧑‍🏫':persona==='Motivator'?'💡':'🧑‍⚕️');
      let time = ts ? `<span class="text-xs text-gray-400">${ts}</span>` : '';
      chat.innerHTML += `<div class="flex ${role==='user'?'justify-end':'justify-start'}">
        <div class="flex items-center space-x-2">
          <span>${avatar}</span>
          <div class="bg-${role==='user'?'blue':'gray'}-100 px-3 py-2 rounded shadow">${text} ${time}</div>
        </div>
      </div>`;
      chat.scrollTop = chat.scrollHeight;
    }
    function showTyping() {
      let chat = document.getElementById('chat');
      chat.innerHTML += `<div id="typing" class="typing-indicator text-gray-400">AI is thinking...</div>`;
      chat.scrollTop = chat.scrollHeight;
    }
    function hideTyping() {
      let typing = document.getElementById('typing');
      if (typing) typing.remove();
    }
    // Personalized greeting on load
    fetch('Aichatbot.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({message: 'greet', persona, lang})
    })
    .then(r=>r.json()).then(data=>{
      let chat = document.getElementById('chat');
      chat.innerHTML += `<div class="text-center text-lg font-semibold mb-2">👋 Welcome, ${data.name}!</div>`;
    });
    
    // Accessibility: font size toggle
    let fontSize = 16;
    const fontBtn = document.createElement('button');
    fontBtn.textContent = '🔠';
    fontBtn.className = 'ml-2 px-2 py-1 border rounded';
    fontBtn.onclick = () => {
      fontSize = fontSize === 16 ? 20 : 16;
      document.getElementById('chat').style.fontSize = fontSize + 'px';
    };
    document.querySelector('.p-4.border-b div').appendChild(fontBtn);
    
    // XP level badge
    function updateXPBar(xp) {
      let bar = document.getElementById('xp-bar');
      let percent = Math.min(100, xp*2);
      bar.style.width = percent + '%';
      bar.className = percent>80?'bg-green-400':'bg-blue-400';
      let level = Math.floor(xp/50)+1;
      bar.innerHTML = `<span class="text-xs ml-2">Level ${level}</span>`;
    }
    
    // Show last 5 moods
    function showMoodHistory() {
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'mood_history', persona, lang})
      })
      .then(r=>r.json()).then(data=>{
        let moodBar = document.createElement('div');
        moodBar.className = 'flex space-x-1 mt-2';
        moodBar.innerHTML = data.moods.map(m=>`<span title="${m.time}">${m.emoji}</span>`).join('');
        document.querySelector('.p-2.flex.items-center').appendChild(moodBar);
      });
    }
    showMoodHistory();
    
    // Mic button for voice input
    const micBtn = document.createElement('button');
    micBtn.textContent = '🎤';
    micBtn.className = 'ml-2 px-2 py-1 border rounded';
    document.querySelector('.p-4.border-b div').appendChild(micBtn);

    micBtn.onclick = () => {
      const recognition = new(window.SpeechRecognition || window.webkitSpeechRecognition)();
      recognition.lang = lang === 'BN' ? 'bn-BD' : 'en-US';
      recognition.start();
      recognition.onresult = e => {
        document.getElementById('msg').value = e.results[0][0].transcript;
      };
    };

    // Speak out bot responses
    function speak(text) {
      const synth = window.speechSynthesis;
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = lang === 'BN' ? 'bn-BD' : 'en-US';
      synth.speak(utter);
    }

    // Dashboard button functionality
    document.getElementById('dashboardBtn').onclick = () => {
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'dashboard', persona, lang})
      })
      .then(r=>r.json()).then(data=>{
        document.getElementById('xpProgress').innerHTML = `XP: ${data.xp}`;
        document.getElementById('moodTrend').innerHTML = `Mood Trend: ${data.moodTrend}`;
        document.getElementById('taskStats').innerHTML = `Tasks: ${data.tasks}`;
        document.getElementById('dashboardModal').classList.remove('hidden');
      });
    };

    // Challenges button functionality
    document.getElementById('challengeBtn').onclick = () => {
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'challenges', persona, lang})
      })
      .then(r=>r.json()).then(data=>{
        document.getElementById('challengeList').innerHTML = data.challenges.map(c=>`<div>${c.name} - ${c.status}</div>`).join('');
        document.getElementById('challengeModal').classList.remove('hidden');
      });
    };

    // Peer chat button functionality
    document.getElementById('peerBtn').onclick = () => {
      // Open peer chat modal, fetch peer match from PHP
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'peer_chat', persona, lang})
      })
      .then(r=>r.json()).then(data=>{
        // Show peer chat UI and relay messages
      });
    };

    // Mindfulness button functionality
    document.getElementById('mindfulnessBtn').onclick = () => {
      // Show breathing animation or guided meditation
      alert('Breathe in... Breathe out...');
    };
    // Emergency help button functionality
    document.getElementById('helpBtn').onclick = () => {
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'helpline', persona, lang})
      })
      .then(r=>r.json()).then(data=>{
        alert('Helpline: ' + data.helpline);
      });
    };

    // Upload notes and summarize
    document.getElementById('uploadBtn').onclick = () => {
      let file = document.getElementById('noteFile').files[0];
      let formData = new FormData();
      formData.append('file', file);
      formData.append('quick', 'summarize_file');
      fetch('Aichatbot.php', {method:'POST', body:formData})
        .then(r=>r.json()).then(data=>{
          showMsg('bot', data.reply, persona);
        });
    };

    // Privacy button functionality
    document.getElementById('privacyBtn').onclick = () => {
      document.getElementById('privacyModal').classList.remove('hidden');
    };
    document.getElementById('privateMode').onchange = e => {
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'privacy', private: e.target.checked})
      });
    };

    // Persona button functionality
    document.getElementById('personaBtn').onclick = () => {
      document.getElementById('personaModal').classList.remove('hidden');
    };
    document.getElementById('personaType').onchange = e => {
      persona = e.target.value;
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'set_persona', persona})
      });
    };

    // Ask for info step by step, allow skip
const userProfile = {};
const questions = [
  {key:'university', text:'Which university do you attend?', optional:true},
  {key:'course', text:'What is your main course or major?', optional:true},
  {key:'study_habits', text:'Describe your study habits (e.g., morning/evening, group/self)?', optional:true},
  {key:'prayer_time', text:'Do you have a regular prayer time?', optional:true},
  {key:'sleep_schedule', text:'What is your usual sleep schedule?', optional:true}
];
let qIndex = 0;

function askNextQuestion() {
  if (qIndex < questions.length) {
    showMsg('bot', questions[qIndex].text + ' (or type "skip")', persona);
  }
}
function handleUserInput(msg) {
  if (qIndex < questions.length) {
    if (msg.toLowerCase() !== 'skip') userProfile[questions[qIndex].key] = msg;
    qIndex++;
    askNextQuestion();
    if (qIndex === questions.length) {
      // Send profile to backend for personalization
      fetch('Aichatbot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({message: 'profile', profile: userProfile, persona, lang})
      })
      .then(r=>r.json()).then(data=>{
        showMsg('bot', data.reply, persona);
      });
    }
  } else {
    sendMsg(msg); // Normal chat after profile collection
  }
}
// Replace sendMsg with handleUserInput during onboarding

    window.addEventListener('offline', () => {
      alert('You are offline. Your notes and tasks will be saved locally.');
    });
    window.addEventListener('online', () => {
      alert('You are back online. Syncing data...');
      // Sync localStorage data to server
    });
  </script>
</body>
</html>
<?php
session_start();
header('Content-Type: application/json');

// --- CONFIG ---
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chatbot_db');
define('MENTOR_EMAIL', 'mentor@university.edu');

// --- DB CONNECT ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// --- UTILS ---
function getUserId() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = uniqid('user_');
    }
    return $_SESSION['user_id'];
}

function getUserName($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id=?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = $result->fetch_assoc()['name'] ?? 'Student';
    $stmt->close();
    return $name;
}

function saveChatLog($userId, $role, $message, $persona, $lang) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, role, message, persona, lang, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $userId, $role, $message, $persona, $lang);
    $stmt->execute();
    $stmt->close();
}

function fetchContext($userId, $limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT role, message FROM chat_logs WHERE user_id=? ORDER BY id DESC LIMIT ?");
    $stmt->bind_param("si", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $context = [];
    while ($row = $result->fetch_assoc()) {
        $context[] = ['role' => $row['role'], 'content' => $row['message']];
    }
    $stmt->close();
    return array_reverse($context);
}

function sendMentorEmail($userId, $question) {
    $to = MENTOR_EMAIL;
    $subject = "Student Escalation: $userId";
    $body = "Student ($userId) needs mentor help:\n\n$question";
    mail($to, $subject, $body);
}

function updateXP($userId, $xp) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE user_id=?");
    $stmt->bind_param("is", $xp, $userId);
    $stmt->execute();
    $stmt->close();
}

function saveMood($userId, $mood) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO moods (user_id, mood, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $userId, $mood);
    $stmt->execute();
    $stmt->close();
}

function addAssignment($userId, $title, $due) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO assignments (user_id, title, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $userId, $title, $due);
    $stmt->execute();
    $stmt->close();
}

function getAssignments($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT title, due_date FROM assignments WHERE user_id=? ORDER BY due_date ASC");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
    return $assignments;
}

function getLastMoods($userId, $limit=5) {
    global $conn;
    $stmt = $conn->prepare("SELECT mood, created_at FROM moods WHERE user_id=? ORDER BY id DESC LIMIT ?");
    $stmt->bind_param("si", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $moods = [];
    while ($row = $result->fetch_assoc()) {
        $emoji = [
            'happy'=>'😊','sad'=>'😢','stressed'=>'😰','excited'=>'🤩',''=>'😐'
        ][$row['mood']] ?? '😐';
        $moods[] = ['emoji'=>$emoji, 'time'=>$row['created_at']];
    }
    $stmt->close();
    return $moods;
}

// --- OPENAI REQUEST ---
function askOpenAI($messages, $persona, $lang) {
    $systemPrompt = [
        "Mentor" => "You are a wise academic mentor for university students.",
        "Motivator" => "You are a motivational coach for students.",
        "Listener" => "You are a supportive mental wellness listener.",
    ][$persona] ?? "You are a helpful assistant for university students.";

    if ($lang === 'BN') {
        $systemPrompt .= " Respond in Bangla language.";
    }

    array_unshift($messages, [
        "role" => "system",
        "content" => $systemPrompt
    ]);

    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => $messages,
        "temperature" => 0.7
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . OPENAI_API_KEY,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? "Sorry, I couldn't process your request.";
}

// --- MAIN LOGIC ---
$userId = getUserId();
$input = json_decode(file_get_contents('php://input'), true);

$message = $input['message'] ?? '';
$persona = $input['persona'] ?? 'Mentor';
$lang = $input['lang'] ?? 'EN';
$mood = $input['mood'] ?? null;

// --- API endpoints for frontend features ---
if ($message === 'greet') {
    echo json_encode(['name'=>getUserName($userId)]);
    $conn->close();
    exit;
}
if ($message === 'mood_history') {
    echo json_encode(['moods'=>getLastMoods($userId)]);
    $conn->close();
    exit;
}
if ($message === 'get_progress') {
    $xp = $_SESSION['xp'] ?? 0;
    $mood = getLastMoods($userId, 1)[0]['mood'] ?? '';
    $tasks = count(getAssignments($userId));
    echo json_encode(['xp' => $xp, 'mood' => $mood, 'tasks' => $tasks]);
    $conn->close();
    exit;
}

// --- Assignment Tracker ---
if ($input['quick'] === 'add_assignment' && isset($input['title'], $input['due'])) {
    addAssignment($userId, $input['title'], $input['due']);
    echo json_encode(['reply' => "Assignment added: {$input['title']} (Due: {$input['due']})"]);
    $conn->close();
    exit;
}
if ($input['quick'] === 'view_assignments') {
    $assignments = getAssignments($userId);
    $reply = "Your assignments:\n";
    foreach ($assignments as $a) {
        $reply .= "- {$a['title']} (Due: {$a['due_date']})\n";
    }
    echo json_encode(['reply' => $reply]);
    $conn->close();
    exit;
}

// --- Note Summarizer ---
if ($input['quick'] === 'summarize' && isset($input['text'])) {
    $summaryPrompt = [
        ['role'=>'system','content'=>'Summarize the following notes for a university student.'],
        ['role'=>'user','content'=>$input['text']]
    ];
    $summary = askOpenAI($summaryPrompt, $persona, $lang);
    echo json_encode(['reply' => $summary]);
    $conn->close();
    exit;
}

// --- Research Assistant ---
if ($input['quick'] === 'research' && isset($input['topic'])) {
    $researchPrompt = [
        ['role'=>'system','content'=>'Act as a research assistant. Find 3 recent papers and generate an outline for the topic.'],
        ['role'=>'user','content'=>$input['topic']]
    ];
    $research = askOpenAI($researchPrompt, $persona, $lang);
    echo json_encode(['reply' => $research]);
    $conn->close();
    exit;
}

// --- CV Builder ---
if ($input['quick'] === 'cv_builder' && isset($input['details'])) {
    $cvPrompt = [
        ['role'=>'system','content'=>'Help build a professional CV for a university student.'],
        ['role'=>'user','content'=>$input['details']]
    ];
    $cv = askOpenAI($cvPrompt, $persona, $lang);
    echo json_encode(['reply' => $cv]);
    $conn->close();
    exit;
}

// --- Interview Practice ---
if ($input['quick'] === 'interview' && isset($input['job'])) {
    $interviewPrompt = [
        ['role'=>'system','content'=>'Simulate a mock interview for the following job.'],
        ['role'=>'user','content'=>$input['job']]
    ];
    $interview = askOpenAI($interviewPrompt, $persona, $lang);
    echo json_encode(['reply' => $interview]);
    $conn->close();
    exit;
}

// --- Save user message ---
saveChatLog($userId, 'user', $message, $persona, $lang);

// --- Context memory ---
$context = fetchContext($userId);

// --- Feature triggers ---
$specialResponse = null;
if (stripos($message, 'study planner') !== false || $input['quick'] === 'planner') {
    $specialResponse = "Here's your daily study planner:\n- 9am: Review notes\n- 10am: Practice problems\n- 12pm: Break\n- 1pm: Group study\n- 3pm: Assignment work\n- 5pm: Pomodoro session\n- 7pm: Reflection & journal";
}
if (stripos($message, 'pomodoro') !== false || $input['quick'] === 'pomodoro') {
    $specialResponse = "Pomodoro reminder set! Work for 25 minutes, then take a 5-minute break. Repeat 4 times, then take a longer break.";
}
if (stripos($message, 'motivation') !== false || $input['quick'] === 'motivation') {
    $specialResponse = "Remember: Every small step counts. Stay focused, and believe in yourself!";
}
if (stripos($message, 'journal') !== false || $input['quick'] === 'journal') {
    $specialResponse = "How are you feeling today? Write your thoughts and track your mood.";
}
if (stripos($message, 'career') !== false || $input['quick'] === 'career') {
    $specialResponse = "For career advice, update your resume regularly and seek internships. Want tips for scholarships?";
}
if (stripos($message, 'mentor') !== false || $input['quick'] === 'mentor') {
    sendMentorEmail($userId, $message);
    $specialResponse = "Your request has been forwarded to a real mentor. Expect a reply soon!";
}
if (stripos($message, 'resource') !== false || $input['quick'] === 'resource') {
    $specialResponse = "Here are some resources:\n- Khan Academy: https://khanacademy.org\n- Coursera: https://coursera.org\n- Google Scholar: https://scholar.google.com";
}
if ($input['quick'] === 'scholarship') {
    $specialResponse = "Here are some scholarship portals:\n- https://scholarships.com\n- https://www.topuniversities.com/scholarships";
}
if ($input['quick'] === 'group_study' && isset($input['time'])) {
    $specialResponse = "Group study session scheduled at {$input['time']}. Invite your friends!";
}

// --- AI Response ---
if ($specialResponse) {
    $aiResponse = $specialResponse;    'xp' => $xp,
} else {na,
    $aiResponse = askOpenAI($context, $persona, $lang);> $lang,    'name' => getUserName($userId),
}erId),date('Y-m-d H:i:s')
   'timestamp' => date('Y-m-d H:i:s')
// --- Save AI response ---]);
saveChatLog($userId, 'assistant', $aiResponse, $persona, $lang);// --- XP Reward Logic ---$xp = rand(1, 5); // Simple XP reward per messageupdateXP($userId, $xp);// --- Mood Tracker ---if ($mood) {    saveMood($userId, $mood);}
// --- RESPONSE ---
echo json_encode([
    'reply' => $aiResponse,

// --- Mood Tracker ---
if ($mood) {
    saveMood($userId, $mood);
}

// --- RESPONSE ---
echo json_encode([
    'reply' => $aiResponse,
    'xp' => $xp,
    'persona' => $persona,
    'lang' => $lang,    'name' => getUserName($userId),
    'name' => getUserName($userId),date('Y-m-d H:i:s')    'timestamp' => date('Y-m-d H:i:s')
]);

$conn->close();