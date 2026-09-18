<?php
// session_start();
require_once 'config/db.php';
require_once 'inc/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: login.php'); exit;
}

if (isset($_GET['student_id'])) {
    $_SESSION['chat_student_id'] = intval($_GET['student_id']);
}
$student_id = $_SESSION['chat_student_id'] ?? 0;

$supervisor_id = intval($_SESSION['supervisor_id']);
// $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$dbb = new operations();

// Optional: fetch student details for header (simple query)
$student = null;
if ($student_id) {
    $res = mysqli_query($db->connection, "SELECT student_id, name, reg_no FROM students WHERE student_id = '$student_id' LIMIT 1");
    if ($res) $student = mysqli_fetch_assoc($res);
}
?>

<!DOCTYPE html>
<html lang="en-US">
<body>
<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Chat with Student</h4>
        <a href="officer_dashboard.php" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <?php if (!$student_id || !$student): ?>
        <div class="alert alert-info">Select a student from your assigned list to chat.</div>
        <div>
          <a href="assigned_donors.php" class="btn btn-primary">View Assigned Students</a>
        </div>
    <?php else: ?>

    <div class="card">
        <div class="card-header">
            <strong><?php echo htmlspecialchars($student['name']); ?></strong>
            <small class="text-muted ms-2"><?php echo htmlspecialchars($student['reg_no']); ?></small>
        </div>
        <div class="card-body" style="height:400px; overflow:auto;" id="chat-window">
            <!-- messages loaded by JS -->
            <div id="messages"></div>
        </div>
        <div class="card-footer">
            <form id="sendForm" onsubmit="return sendMessage();">
                <div class="input-group">
                    <input id="msgInput" type="text" class="form-control" placeholder="Type message..." autocomplete="off" required>
                    <button class="btn btn-primary" type="submit">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const supervisorId = <?php echo $supervisor_id; ?>;
    const studentId = <?php echo $student_id; ?>;
    const sender = 'supervisor';

    async function fetchMessages() {
        const res = await fetch(`chat_api.php?supervisor_id=${supervisorId}&student_id=${studentId}`);
        const data = await res.json();
        if (!data.success) return;
        const messages = data.messages;
        const container = document.getElementById('messages');
        container.innerHTML = '';
        for (const m of messages) {
            const el = document.createElement('div');
            el.className = (m.sender === 'supervisor') ? 'text-end mb-2' : 'text-start mb-2';
            const bubble = document.createElement('div');
            bubble.innerHTML = `<div class="d-inline-block p-2 rounded ${m.sender === 'supervisor' ? 'bg-primary text-white' : 'bg-light text-dark'}" style="max-width:70%;">${escapeHtml(m.message)}<br><small class="text-muted">${m.created_at}</small></div>`;
            el.appendChild(bubble);
            container.appendChild(el);
        }
        // scroll to bottom
        const chatWindow = document.getElementById('chat-window');
        chatWindow.scrollTop = chatWindow.scrollHeight;

        // mark messages read (recipient = supervisor)
        await fetch(`chat_api.php?supervisor_id=${supervisorId}&student_id=${studentId}&mark_read=supervisor`);
    }

    async function sendMessage() {
        const input = document.getElementById('msgInput');
        const text = input.value.trim();
        if (!text) return false;

        const form = new FormData();
        form.append('supervisor_id', supervisorId);
        form.append('student_id', studentId);
        form.append('sender', sender);
        form.append('message', text);

        const res = await fetch('chat_api.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            input.value = '';
            fetchMessages();
        } else {
            alert('Failed to send message.');
        }
        return false;
    }

    function escapeHtml(s){
      return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
    }

    // initial fetch + polling
    fetchMessages();
    setInterval(fetchMessages, 3000);
    </script>

    <?php endif; ?>
</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>
