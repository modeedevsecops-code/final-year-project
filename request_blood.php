<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
require_once 'inc/header.php';

// Recipients only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'recipient') {
    header("Location: login.php");
    exit;
}

$db_conn = $db->connection;

// Adjust this if your recipient id session key is different
$recipientId = $_SESSION['recipient_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$recipientName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Recipient';

$errors = [];
$success = false;

// recipient_id is now declared in db/schema.sql. The runtime ALTER that used to
// live here used MariaDB-only "ADD COLUMN IF NOT EXISTS" syntax, which is a hard
// SQL syntax error on MySQL — and since PHP 8.1 mysqli throws on it, so it took
// the whole page down. (BL-26 / BL-27.) The schema owns this column now.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bl_csrf_check();      // BL-14
    $patientName   = trim($_POST['patient_name'] ?? '');
    $bloodGroup    = trim($_POST['blood_group'] ?? '');
    $unitsNeeded   = (int)($_POST['units_needed'] ?? 0);
    $hospitalName  = trim($_POST['hospital_name'] ?? '');
    $location      = trim($_POST['location'] ?? '');
    $urgencyLevel  = trim($_POST['urgency_level'] ?? '');
    $contactPhone  = trim($_POST['contact_phone'] ?? '');

    $validBloodGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
    $validUrgency = ['Normal','Urgent','Critical Emergency'];

    if ($patientName === '') $errors[] = "Patient name is required.";
    if (!in_array($bloodGroup, $validBloodGroups)) $errors[] = "Select a valid blood group.";
    if ($unitsNeeded < 1) $errors[] = "Units needed must be at least 1.";
    if (!in_array($urgencyLevel, $validUrgency)) $errors[] = "Select an urgency level.";
    if ($hospitalName === '') $errors[] = "Hospital name is required.";
    if ($location === '') $errors[] = "Hospital location is required.";
    if ($contactPhone === '') $errors[] = "Contact phone is required.";
    if (!$recipientId) $errors[] = "Session error: could not identify recipient. Please log in again.";

    if (empty($errors)) {
        $checkStmt = mysqli_prepare($db_conn,
            "SELECT request_id FROM blood_requests
             WHERE recipient_id = ? AND blood_group = ? AND status = 'Pending'
             LIMIT 1"
        );
        mysqli_stmt_bind_param($checkStmt, "is", $recipientId, $bloodGroup);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $errors[] = "You already have a pending $bloodGroup request. Check your Request History.";
        }
    }

    if (empty($errors)) {
        // Geocode the hospital location once, at submission, so the request
        // appears on the map (Phase 3, BL-05). NULL coords if it can't be found.
        list($reqLat, $reqLng) = bl_geocode($hospitalName . ', ' . $location);

        $stmt = mysqli_prepare($db_conn,
            "INSERT INTO blood_requests
                (recipient_id, patient_name, blood_group, units_needed, hospital_name, location, latitude, longitude, urgency_level, status, requested_by, contact_phone)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt, "ississddsss",
            $recipientId, $patientName, $bloodGroup, $unitsNeeded,
            $hospitalName, $location, $reqLat, $reqLng, $urgencyLevel, $recipientName, $contactPhone
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = true;

            if ($urgencyLevel === 'Critical Emergency') {
                $title = "Emergency: $bloodGroup blood needed at $hospitalName";
                $message = "$unitsNeeded unit(s) of $bloodGroup needed urgently for patient $patientName at $hospitalName ($location). Contact: $contactPhone.";
                $alertStmt = mysqli_prepare($db_conn,
                    "INSERT INTO notices (title, message, supervisor_id, created_at) VALUES (?, ?, NULL, NOW())"
                );
                mysqli_stmt_bind_param($alertStmt, "ss", $title, $message);
                mysqli_stmt_execute($alertStmt);
            }
        } else {
            $errors[] = "Something went wrong saving your request. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<?php include 'inc/header.php'; ?>
<body>

<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content rb-content">
        <div class="container-fluid rb-page">

            <div class="rb-header">
                <div class="rb-eyebrow">Blood Request</div>
                <h1 class="rb-title">New Blood Request</h1>
                <p class="rb-subtitle">Submit patient and hospital details. Critical Emergency requests are flagged immediately.</p>
            </div>

            <?php if ($success): ?>
                <div class="rb-notice rb-notice-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                    <div>
                        Your blood request has been submitted successfully.
                        <a href="request_history.php">View request history &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="rb-notice rb-notice-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="rb-form">

                <div class="rb-colored-panel">
                    <section class="rb-section">
                        <h2 class="rb-section-title">Urgency Level</h2>
                        <div class="rb-urgency-grid">
                            <label class="rb-urgency-option" data-level="normal">
                                <input type="radio" name="urgency_level" value="Normal" checked form="rbForm">
                                <span class="rb-urgency-icon">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="8" r="7"/></svg>
                                </span>
                                <span class="rb-urgency-text">
                                    <strong>Normal</strong>
                                    <small>Scheduled, no immediate risk</small>
                                </span>
                            </label>
                            <label class="rb-urgency-option" data-level="urgent">
                                <input type="radio" name="urgency_level" value="Urgent" form="rbForm">
                                <span class="rb-urgency-icon">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.964 0L.165 13.233c-.457.778.091 1.767.982 1.767h13.706c.89 0 1.438-.99.982-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                                </span>
                                <span class="rb-urgency-text">
                                    <strong>Urgent</strong>
                                    <small>Needed within 24&ndash;48 hrs</small>
                                </span>
                            </label>
                            <label class="rb-urgency-option" data-level="critical">
                                <input type="radio" name="urgency_level" value="Critical Emergency" form="rbForm">
                                <span class="rb-urgency-icon">
                                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917z"/></svg>
                                </span>
                                <span class="rb-urgency-text">
                                    <strong>Critical Emergency</strong>
                                    <small>Immediate / life&#8209;threatening</small>
                                </span>
                            </label>
                        </div>
                    </section>

                    <div class="rb-two-col">
                        <section class="rb-section">
                            <h2 class="rb-section-title">Patient Details</h2>
                            <div class="rb-grid">
                                <div class="rb-field rb-span-12">
                                    <label>Patient Name</label>
                                    <input type="text" name="patient_name" required form="rbForm"
                                           value="<?php echo htmlspecialchars($_POST['patient_name'] ?? ''); ?>">
                                </div>
                                <div class="rb-field rb-span-6">
                                    <label>Blood Group Needed</label>
                                    <select name="blood_group" required form="rbForm">
                                        <option value="">Select</option>
                                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                            <option value="<?php echo $bg; ?>"
                                                <?php echo (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : ''; ?>>
                                                <?php echo $bg; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="rb-field rb-span-6">
                                    <label>Units Needed</label>
                                    <input type="number" name="units_needed" min="1" max="20" required form="rbForm"
                                           value="<?php echo htmlspecialchars($_POST['units_needed'] ?? 1); ?>">
                                </div>
                            </div>
                        </section>

                        <section class="rb-section">
                            <h2 class="rb-section-title">Hospital &amp; Contact</h2>
                            <div class="rb-grid">
                                <div class="rb-field rb-span-12">
                                    <label>Hospital Name</label>
                                    <input type="text" name="hospital_name" required form="rbForm"
                                           placeholder="e.g. Barau Dikko Teaching Hospital"
                                           value="<?php echo htmlspecialchars($_POST['hospital_name'] ?? ''); ?>">
                                </div>
                                <div class="rb-field rb-span-6">
                                    <label>Location / City</label>
                                    <input type="text" name="location" required form="rbForm"
                                           placeholder="e.g. Kaduna North"
                                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                                </div>
                                <div class="rb-field rb-span-6">
                                    <label>Contact Phone</label>
                                    <input type="text" name="contact_phone" required form="rbForm"
                                           placeholder="080XXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <form method="POST" action="request_blood.php" id="rbForm" class="rb-actions">
                    <?php bl_csrf_field(); // BL-14 ?>
                    <a href="recipient_dashboard.php" class="rb-btn rb-btn-ghost">Cancel</a>
                    <button type="submit" class="rb-btn rb-btn-primary">Submit Request</button>
                </form>
            </div>

        </div>
    </div>
</main>

<style>
/* Force natural height — override any site-wide min-height on .main-content */
.main-content.rb-content,
.rb-content .container-fluid,
.rb-form,
.rb-section,
.rb-two-col {
    min-height: 0 !important;
    height: auto !important;
    flex: none !important;
}

.rb-content {
    background: linear-gradient(180deg, #fdf2f2 0%, #f3f0fb 45%, #eef2fb 100%);
}

.rb-page {
    max-width: 1040px;
    margin: 0 auto;
    padding: 1.25rem 1rem 2.5rem;
}

.rb-header { margin-bottom: 0.85rem; }
.rb-eyebrow {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #b3261e;
    margin-bottom: 0.2rem;
}
.rb-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #1c1c1e;
    margin: 0 0 0.2rem;
}
.rb-subtitle {
    color: #6b7280;
    font-size: 0.85rem;
    margin: 0;
    max-width: 620px;
}

.rb-notice {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.6rem 0.9rem;
    border-radius: 10px;
    font-size: 0.83rem;
    margin-bottom: 0.75rem;
}
.rb-notice-success {
    background: #ecfdf3;
    border: 1px solid #a6e9c2;
    color: #056130;
}
.rb-notice-success a { color: #056130; font-weight: 600; text-decoration: underline; }
.rb-notice-error {
    background: #fef2f2;
    border: 1px solid #f3b3b0;
    color: #9a1c1c;
}
.rb-notice-error ul { margin: 0; padding-left: 1.1rem; }

.rb-form {
    background: #ffffff;
    border: 1px solid #eeeeee;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(90, 20, 30, 0.08);
    padding: 1.1rem 1.1rem 1.2rem;
}

/* Continuous colored panel spanning Urgency Level through Patient/Hospital details */
.rb-colored-panel {
    background: linear-gradient(135deg, #fff5f5 0%, #f6f3fd 55%, #eef2fb 100%);
    border: 1px solid #f0e6ee;
    border-radius: 12px;
    padding: 0.85rem 0.95rem 1rem;
}

.rb-section { margin-bottom: 0.75rem; }
.rb-section:last-of-type { margin-bottom: 0; }
.rb-section-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8f8f9a;
    margin: 0 0 0.5rem;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}

.rb-urgency-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}
@media (max-width: 720px) { .rb-urgency-grid { grid-template-columns: 1fr; } }

.rb-urgency-option {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1.5px solid #e5e5e8;
    border-radius: 10px;
    padding: 0.5rem 0.7rem;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    background: #ffffff;
}
.rb-urgency-option input { position: absolute; opacity: 0; }
.rb-urgency-icon {
    width: 24px;
    height: 24px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: color-mix(in srgb, currentColor 12%, white);
    color: inherit;
}
.rb-urgency-text { display: flex; flex-direction: column; line-height: 1.2; }
.rb-urgency-text strong { font-size: 0.81rem; color: #1c1c1e; }
.rb-urgency-text small { color: #8a8a90; font-size: 0.68rem; }

.rb-urgency-option[data-level="normal"] { color: #2f8f5b; }
.rb-urgency-option[data-level="urgent"] { color: #b8860b; }
.rb-urgency-option[data-level="critical"] { color: #cc0000; }

.rb-urgency-option:has(input:checked) {
    border-color: currentColor;
    background: color-mix(in srgb, currentColor 7%, white);
    box-shadow: 0 0 0 1px currentColor inset;
}

.rb-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 1.6rem;
    align-items: start;
    margin-top: 0.75rem;
}
@media (max-width: 780px) { .rb-two-col { grid-template-columns: 1fr; } }

.rb-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 0.6rem 0.55rem;
}
.rb-span-12 { grid-column: span 12; }
.rb-span-6 { grid-column: span 6; }

.rb-field label {
    display: block;
    font-size: 0.73rem;
    font-weight: 600;
    color: #4b4b50;
    margin-bottom: 0.22rem;
}
.rb-field input,
.rb-field select {
    width: 100%;
    padding: 0.48rem 0.62rem;
    border: 1.5px solid #e2e2e5;
    border-radius: 8px;
    font-size: 0.82rem;
    color: #1c1c1e;
    background: #ffffff;
    transition: border-color .15s ease, background .15s ease;
}
.rb-field input::placeholder { color: #b5b5ba; }
.rb-field input:focus,
.rb-field select:focus {
    outline: none;
    border-color: #cc0000;
    background: #ffffff;
}

.rb-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f0f0f2;
}
.rb-btn {
    padding: 0.52rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.82rem;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.rb-btn-ghost { background: #ffffff; border: 1.5px solid #e2e2e5; color: #4b4b50; }
.rb-btn-ghost:hover { background: #f7f7f8; }
.rb-btn-primary { background: #cc0000; color: #ffffff; }
.rb-btn-primary:hover { background: #a80000; }
</style>

<?php include 'inc/main_js.php'; ?>
</body>
</html>