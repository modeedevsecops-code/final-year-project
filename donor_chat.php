<?php
require_once 'config/db.php';
require_once 'inc/header.php';

// Only students allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php'); exit;
}

$student_id = intval($_SESSION['user_id']);
$dbb = new operations();

// Get the assigned supervisor and basic details (you already have a function)
/* reuse your get_student_supervisor() which returns staff_name, email, phone, position, project_title
   but we need supervisor_id too. If your function doesn't return supervisor_id, you can query below:
*/
$res = mysqli_query($db->connection, "SELECT p.assigned_supervisor AS supervisor_id, st.staff_name, st.email FROM projects p JOIN staff st ON st.staff_id = p.assigned_supervisor WHERE p.assigned_student = '$student_id' LIMIT 1");
$supervisor = $res ? mysqli_fetch_assoc($res) : null;
$supervisor_id = $supervisor ? intval($supervisor['supervisor_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en-US">
<body>
<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Chat with Supervisor</h4>
        <a href="donor_dashboard.php" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <?php if (!$supervisor || !$supervisor_id): ?>
        <div class="alert alert-info">No supervisor assigned yet. You can chat once a supervisor is assigned.</div>
    <?php else: ?>
    <div class="card">
        <div class="card-header">
            <strong><?php echo htmlspecialchars($supervisor['staff_name']); ?></strong>
            <small class="text-muted ms-2"><?php echo htmlspecialchars($supervisor['email']); ?></small>
        </div>
        <div class="card-body" style="height:400px; overflow:auto;" id="chat-window">
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
    const sender = 'student';

    async function fetchMessages() {
        const res = await fetch(`chat_api.php?supervisor_id=${supervisorId}&student_id=${studentId}`);
        const data = await res.json();
        if (!data.success) return;
        const messages = data.messages;
        const container = document.getElementById('messages');
        container.innerHTML = '';
        for (const m of messages) {
            const el = document.createElement('div');
            el.className = (m.sender === 'student') ? 'text-end mb-2' : 'text-start mb-2';
            const bubble = document.createElement('div');
            bubble.innerHTML = `<div class="d-inline-block p-2 rounded ${m.sender === 'student' ? 'bg-primary text-white' : 'bg-light text-dark'}" style="max-width:70%;">${escapeHtml(m.message)}<br><small class="text-muted">${m.created_at}</small></div>`;
            el.appendChild(bubble);
            container.appendChild(el);
        }
        const chatWindow = document.getElementById('chat-window');
        chatWindow.scrollTop = chatWindow.scrollHeight;

        // mark read (recipient = student)
        await fetch(`chat_api.php?supervisor_id=${supervisorId}&student_id=${studentId}&mark_read=student`);
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

    fetchMessages();
    setInterval(fetchMessages, 3000);
    </script>

    <?php endif; ?>
</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>
