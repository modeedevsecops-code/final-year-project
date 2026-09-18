<?php
// Errors are always logged, but only shown on screen when APP_DEBUG is on.
// It defaults to OFF so stack traces, SQL and file paths never leak to users
// (BL-24). Turn it on for local dev by putting  define('APP_DEBUG', true);
// in config/config.local.php.
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');

// Since PHP 8.1 mysqli throws on any failed query (BL-26). This codebase mostly
// doesn't check return values, so a stray failure would otherwise be a blank
// 500. Log every uncaught error and show a friendly page in production; show the
// detail only when APP_DEBUG is on.
set_exception_handler(function ($e) {
    error_log('[BloodLink] Uncaught ' . get_class($e) . ': ' . $e->getMessage()
              . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) { http_response_code(500); }
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre style="white-space:pre-wrap">' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8">'
           . '<div style="font-family:system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#333">'
           . '<h2 style="color:#7a0000">Something went wrong</h2>'
           . '<p>An unexpected error occurred. Please try again, or contact the administrator if it persists.</p>'
           . '</div>';
    }
    exit;
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('config/db.php');
$db = new dbconfig();

class operations extends dbconfig{

    //Saving Records
    public function add_staff(){
        
        global $db;

        if (isset($_POST['btn_add_staff'])) {

            $name = $db->check($_POST['name']);
            $email = $db->check($_POST['email']);
            $phone = $db->check($_POST['phone']);
            $position = $db->check($_POST['position']);
            # code...

            
            if ($this->insert_record($name, $email, $phone, $position)) {

                $this->set_message('<div class="alert alert-success text-center"> Staff Added Successfully</div>');
                 ?>
                    <script>
                        setTimeout(() => window.location.href = "", 2000);
                    </script>

                <?php
                # code...
            }else{
                $this->set_message('<div class="alert alert-danger"> Failed to Add record! </div>');
            }
        }

    }

     //Inserting Record into the Database
    function insert_record($name, $email, $phone, $position){

        global $db;

        $query = "INSERT INTO staff (staff_name, email, phone, position) VALUES('$name','$email','$phone','$position')";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{
            return false;
        }
    }


// In your operations class
public function add_project() {
    global $db;

    if (isset($_POST['btn_add_project'])) {
        $title = $db->check($_POST['title']);
        $assigned_student = $db->check($_POST['assigned_student']);
        $assigned_supervisor = $db->check($_POST['assigned_supervisor']);
        $status = $db->check($_POST['status']);
        $methodology = $db->check($_POST['methodology']);
        $description = $db->check($_POST['description']);

        // Validate required fields
        if (!empty($title) && !empty($assigned_student) && !empty($assigned_supervisor) && !empty($status) && !empty($methodology) && !empty($description)) {
            if ($this->insert_project($title, $assigned_student, $assigned_supervisor, $status, $methodology, $description)) {
                $this->set_message('<div class="alert alert-success text-center"> Project Added Successfully</div>');
                ?>
                <script>
                    setTimeout(() => window.location.href = "", 2000);
                </script>
                <?php
            } else {
                $this->set_message('<div class="alert alert-danger"> Failed to Add Project! </div>');
            }
        } else {
            $this->set_message('<div class="alert alert-danger"> Please fill in all fields! </div>');
        }
    }
}

// Inserting Record into the Database
function insert_project($title, $assigned_student, $assigned_supervisor, $status, $methodology, $description) {
    global $db;

    $query = "INSERT INTO projects (title, assigned_student, assigned_supervisor, status, methodology, description) 
              VALUES('$title', '$assigned_student', '$assigned_supervisor', '$status', '$methodology', '$description')";
    $result = mysqli_query($db->connection, $query);

    if ($result) {
        return true;
    } else {
        return false;
    }
}


// In your operations class
public function add_student() {
    global $db;

    if (isset($_POST['btn_add_student'])) {
        $name = $db->check($_POST['name']);
        $email = $db->check($_POST['email']);
        $phone = $db->check($_POST['phone']);
        $department = $db->check($_POST['reg_no']);
        $password = $db->check($_POST['password']);
        // The "year_of_study" select actually carries the blood group; the
        // address field is named donor_address on the admin form.
        $year_of_study = $db->check($_POST['year_of_study'] ?? ($_POST['blood_group'] ?? ''));
        $address = $db->check($_POST['donor_address'] ?? ($_POST['address'] ?? ''));

        // Validate required fields
        if (!empty($name) && !empty($email) && !empty($phone) && !empty($department) && !empty($year_of_study) && !empty($address)) {
            if ($this->insert_student($name, $email, $phone, $department, $year_of_study, $password, $address)) {
                $this->set_message('<div class="alert alert-success text-center"> Student Added Successfully</div>');
                ?>
                <script>
                    setTimeout(() => window.location.href = "", 2000);
                </script>
                <?php
            } else {
                $this->set_message('<div class="alert alert-danger"> Failed to Add Student! </div>');
            }
        } else {
            $this->set_message('<div class="alert alert-danger"> Please fill in all fields! </div>');
        }
    }
}

// Inserting Record into the Database.
// $blood_group is passed in the old $year_of_study slot (the admin form's
// select is named year_of_study but holds the blood group). It is written to
// the real blood_group column now, so admin-added donors are visible to the
// matching engine (BL-01). Geocoding uses Nominatim (Phase 3). Password is
// hashed at creation (BL-12).
function insert_student($name, $email, $phone, $department, $blood_group, $password, $address) {
    global $db;

    list($latitude, $longitude) = bl_geocode($address);
    $lat_val = $latitude !== null ? "'" . floatval($latitude) . "'" : 'NULL';
    $lng_val = $longitude !== null ? "'" . floatval($longitude) . "'" : 'NULL';

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO students (name, email, phone, reg_no, blood_group, password, address, latitude, longitude)
              VALUES ('$name', '$email', '$phone', '$department', '$blood_group', '$password_hash', '$address', $lat_val, $lng_val)";
    $result = mysqli_query($db->connection, $query);

    if ($result) {
        return true;
    } else {
        return false;
    }
}

// Admin creates a recipient account. Was referenced by add_recipient.php but
// never implemented (fatal for admins). Password hashed, address geocoded.
public function add_recipient() {
    global $db;
    if (!isset($_POST['btn_add_recipient'])) return;

    bl_csrf_check(); // BL-14

    $name        = $db->check(trim($_POST['name'] ?? ''));
    $email       = $db->check(trim($_POST['email'] ?? ''));
    $phone       = $db->check(trim($_POST['phone'] ?? ''));
    $blood_group = $db->check(trim($_POST['blood_group'] ?? ''));
    $address_raw = trim($_POST['address'] ?? '');
    $address     = $db->check($address_raw);
    $password    = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $password === '') {
        $this->set_message('<div class="alert alert-danger">Please fill in name, email, phone and password.</div>');
        return;
    }

    // Duplicate email guard (prepared).
    if ($stmt = mysqli_prepare($db->connection, "SELECT recipient_id FROM recipients WHERE email = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $dup = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        if ($dup) {
            $this->set_message('<div class="alert alert-danger">That email is already registered.</div>');
            return;
        }
    }

    list($lat, $lng) = bl_geocode($address_raw);
    $lat_sql = ($lat !== null) ? "'" . floatval($lat) . "'" : 'NULL';
    $lng_sql = ($lng !== null) ? "'" . floatval($lng) . "'" : 'NULL';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO recipients (name, email, phone, address, blood_group, latitude, longitude, password)
              VALUES ('$name', '$email', '$phone', '$address', '$blood_group', $lat_sql, $lng_sql, '$hash')";
    if (mysqli_query($db->connection, $query)) {
        $this->set_message('<div class="alert alert-success text-center">Recipient added successfully.</div>');
        echo '<script>setTimeout(() => window.location.href = "manage_recipients.php", 1500);</script>';
    } else {
        $this->set_message('<div class="alert alert-danger">Failed to add recipient.</div>');
    }
}

// Fetch a single recipient (admin edit).
public function get_recipient_by_id($id) {
    global $db;
    $id = intval($id);
    $res = mysqli_query($db->connection, "SELECT * FROM recipients WHERE recipient_id = $id LIMIT 1");
    return $res ? mysqli_fetch_assoc($res) : null;
}

// Admin updates a recipient (password left unchanged here).
public function update_recipient($id) {
    global $db;
    if (!isset($_POST['btn_update_recipient'])) return;
    bl_csrf_check(); // BL-14

    $id          = intval($id);
    $name        = $db->check(trim($_POST['name'] ?? ''));
    $email       = $db->check(trim($_POST['email'] ?? ''));
    $phone       = $db->check(trim($_POST['phone'] ?? ''));
    $blood_group = $db->check(trim($_POST['blood_group'] ?? ''));
    $address_raw = trim($_POST['address'] ?? '');
    $address     = $db->check($address_raw);

    list($lat, $lng) = bl_geocode($address_raw);
    $lat_sql = ($lat !== null) ? "'" . floatval($lat) . "'" : 'latitude';
    $lng_sql = ($lng !== null) ? "'" . floatval($lng) . "'" : 'longitude';

    $query = "UPDATE recipients
              SET name='$name', email='$email', phone='$phone', address='$address',
                  blood_group='$blood_group', latitude=$lat_sql, longitude=$lng_sql
              WHERE recipient_id=$id";
    if (mysqli_query($db->connection, $query)) {
        $this->set_message('<div class="alert alert-success text-center">Recipient updated.</div>');
        echo '<script>setTimeout(() => window.location.href = "manage_recipients.php", 1500);</script>';
    } else {
        $this->set_message('<div class="alert alert-danger">Failed to update recipient.</div>');
    }
}

public function add_supervisor() {
    global $db;

    if (isset($_POST['btn_add_supervisor'])) {
        // Retrieve and sanitize input data
        $staff_name = $db->check($_POST['staff_name']);
        $phone      = $db->check($_POST['phone']);
        $email      = $db->check($_POST['email']);
        $position   = $db->check($_POST['position'] ?? '');
        $hospital   = $db->check($_POST['hospital'] ?? '');
        $password   = $db->check($_POST['password'] ?? '');

        // Combine position + hospital into one field
        $full_position = $position . ($hospital ? ' – ' . $hospital : '');

        // Insert record into the database
        if ($this->insert_supervisor_record($staff_name, $phone, $email, $full_position, $password)) {
            $this->set_message('<div class="alert alert-success text-center"> Hospital Officer Added Successfully</div>');
            ?>
            <script>
                setTimeout(() => window.location.href = "", 2000);
            </script>
            <?php
        } else {
            $this->set_message('<div class="alert alert-danger"> Failed to Add Hospital Officer! </div>');
        }
    }
}

// Function to insert the hospital officer record into the database
private function insert_supervisor_record($staff_name, $phone, $email, $position, $password = '') {
    global $db;

    $query = "INSERT INTO staff (staff_name, phone, email, position, password)
              VALUES ('$staff_name', '$phone', '$email', '$position', '$password')";
    $result = mysqli_query($db->connection, $query);

    return $result;
}



public function schedule_seminar() {
    global $db;

    if (isset($_POST['btn_schedule_seminar'])) {
        // Retrieve and sanitize input data
        $seminar_title = $db->check($_POST['seminar_title']);
        $date = $db->check($_POST['date']);
        $time = $db->check($_POST['time']);
        $venue = $db->check($_POST['venue']);
        $level = $db->check($_POST['level']);

        // Insert record into the database
        if ($this->insert_seminar_record($seminar_title, $date, $time, $venue, $level)) {
            $this->set_message('<div class="alert alert-success text-center"> Seminar Scheduled Successfully</div>');
            ?>
            <script>
                setTimeout(() => window.location.href = "", 2000); // Redirect after 2 seconds
            </script>
            <?php
        } else {
            $this->set_message('<div class="alert alert-danger"> Failed to Schedule Seminar! </div>');
        }
    }
}

// Verify a plaintext password against a stored value, transparently upgrading
// legacy plaintext rows to a bcrypt hash on the first successful login.
// Returns true if the password matches (BL-12 / BL-13).
private function verify_password($plain, $stored, $table, $pk_col, $pk_val) {
    // Modern path: stored value is a password_hash() result.
    $info = password_get_info($stored);
    if ($info['algo']) {
        return password_verify($plain, $stored);
    }
    // Legacy path: stored value is plaintext from before hashing existed.
    // Constant-time compare, then migrate the row to a hash.
    if (hash_equals((string)$stored, (string)$plain)) {
        $new_hash = password_hash($plain, PASSWORD_DEFAULT);
        $pk_val = intval($pk_val);
        if ($stmt = mysqli_prepare($this->connection, "UPDATE `$table` SET `password` = ? WHERE `$pk_col` = ?")) {
            mysqli_stmt_bind_param($stmt, "si", $new_hash, $pk_val);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        return true;
    }
    return false;
}

public function user_login() {

  if (isset($_POST['btn_user_login'])) {

    $role = $_POST['role'] ?? '';

    if ($role == 'student') {
      // Donor login by reg_no + password (prepared statement — BL-13)
      $reg_no   = trim($_POST['reg_no'] ?? '');
      $password = $_POST['student_password'] ?? '';

      $user = null;
      if ($stmt = mysqli_prepare($this->connection, "SELECT * FROM students WHERE reg_no = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, "s", $reg_no);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
      }

      if ($user && $this->verify_password($password, $user['password'], 'students', 'student_id', $user['student_id'])) {
        $_SESSION['Active'] = 'Active';
        $_SESSION['role'] = 'student';
        $_SESSION['user_id'] = $user['student_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['reg_no'] = $user['reg_no'];

        $this->set_message('<div class="alert alert-success text-center">Login Successful!</div>');
?>
        <script>
          setTimeout(() => window.location.href = "donor_dashboard.php", 1500);
        </script>
<?php
      } else {
        $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid Donor ID or password!</div>');
?>
        <script>
          setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
        </script>
<?php
      }
    }

    elseif ($role == 'recipient') {
            // Recipient login by email or id + password (prepared statement — BL-13)
            $login    = trim($_POST['recipient_email'] ?? '');
            $password = $_POST['recipient_password'] ?? '';

            $user = null;
            if ($stmt = mysqli_prepare($this->connection, "SELECT * FROM recipients WHERE email = ? OR recipient_id = ? LIMIT 1")) {
                $login_id = intval($login);
                mysqli_stmt_bind_param($stmt, "si", $login, $login_id);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);
            }

            if ($user && $this->verify_password($password, $user['password'], 'recipients', 'recipient_id', $user['recipient_id'])) {
                    $_SESSION['Active'] = 'Active';
                    $_SESSION['role'] = 'recipient';
                    $_SESSION['user_id'] = $user['recipient_id'];
                    $_SESSION['recipient_id'] = $user['recipient_id'];
                    $_SESSION['name'] = $user['name'];

                    $this->set_message('<div class="alert alert-success text-center">Login Successful!</div>');
                    ?>
                    <script>
                    setTimeout(() => window.location.href = "recipient_dashboard.php", 1500);
                    </script>
                    <?php
            } else {
                    $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid email or password</div>');
                    ?>
                    <script>
                    setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
                    </script>
                    <?php
            }
        }

    elseif ($role == 'supervisor') {
      // Officer login by email + password (prepared statement — BL-13)
      $email    = trim($_POST['email'] ?? '');
      $password = $_POST['password'] ?? '';

      $user = null;
      if ($stmt = mysqli_prepare($this->connection, "SELECT * FROM staff WHERE email = ? LIMIT 1")) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
      }

      if ($user && $this->verify_password($password, $user['password'], 'staff', 'staff_id', $user['staff_id'])) {
        $_SESSION['Active'] = 'Active';
        $_SESSION['role'] = 'supervisor';
        $_SESSION['user_id'] = $user['staff_id'];
        $_SESSION['supervisor_id'] = $user['staff_id'];
        $_SESSION['name'] = $user['staff_name'];
        $_SESSION['email'] = $user['email'];

        $this->set_message('<div class="alert alert-success text-center">Login Successful!</div>');
?>
        <script>
          setTimeout(() => window.location.href = "officer_dashboard.php", 1500);
        </script>
<?php
      } else {
        $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid Email or Password!</div>');
?>
        <script>
          setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
        </script>
<?php
      }
    }
  }
}


// Function to insert the seminar record into the database
private function insert_seminar_record($seminar_title, $date, $time, $venue, $level) {
    global $db;

    // SQL query to insert a new seminar
    $query = "INSERT INTO seminar (title, seminar_date, seminar_time, venue, level) VALUES ('$seminar_title', '$date', '$time', '$venue', '$level')";
    $result = mysqli_query($db->connection, $query);

    return $result; // Returns true on success, false on failure
}



    //Fetching Records from the Database
    public function fetch_staff_record(){

        global $db;
        $query = "SELECT * FROM staff";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }

    //Getting a particular Record
    public function get_record($id){

        global $db;
        $query = "SELECT * FROM staff WHERE staff_id='$id' ";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }


      //Add Multiple Staff
    public function add_multiple_staff(){
        
        global $db;

        if (isset($_POST['btn_add_multiple_staff'])) {

            $filename = $_FILES["file"]["tmp_name"];
                    $filePath = 'uploads/'.$filename;
                    $tmp_name = $_FILES['file']['tmp_name'];
                    move_uploaded_file($tmp_name, $filePath);

                    if ($_FILES["file"]["size"] > 0) {
                        
                        $file = fopen($filename, "r");
                        while (($column=fgetcsv($file, 1000, ',')) !==FALSE) {
            
                          //echo '<pre>'; print_r($column);


                            if ($this->insert_multiple_record($column[0], $column[1], $column[2], $column[3])) {

                            $this->set_message('<div class="alert alert-success text-center"> Staff Uploaded Successfully</div>');
                             ?>
                                <script>
                                    setTimeout(() => window.location.href = "", 2000);
                                </script>

                                <?php
                                # code...
                            }else{
                                $this->set_message('<div class="alert alert-danger"> Failed to Add record! </div>');
                            }
            
                          // $query2 = mysqli_query($connection, "INSERT INTO staff (staff_name, email, phone, position) VALUES('".$column[0]."', '".$column[1]."', '".$column[2]."', '".$column[3]."')");
                          
                        }
                    }

        }

    }



     //Inserting Record into the Database
    function insert_multiple_record($name, $email, $phone, $position){

        global $db;

        $query = "INSERT INTO staff (staff_name, email, phone, position) VALUES('$name','$email','$phone','$position')";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{
            return false;
        }
    }




       //Add Multiple Staff
    public function add_multiple_data(){
        
        global $db;

        if (isset($_POST['btn_add_multiple_data'])) {

            $filename = $_FILES["file"]["tmp_name"];
                    $filePath = 'uploads/'.$filename;
                    $tmp_name = $_FILES['file']['tmp_name'];
                    move_uploaded_file($tmp_name, $filePath);

                    if ($_FILES["file"]["size"] > 0) {
                        
                        $file = fopen($filename, "r");
                        while (($column=fgetcsv($file, 1000, ',')) !==FALSE) {
            
                          //echo '<pre>'; print_r($column);


                            if ($this->insert_multiple_record($column[0], $column[1], $column[2], $column[3])) {

                            $this->set_message('<div class="alert alert-success text-center"> Staff Uploaded Successfully</div>');
                             ?>
                                <script>
                                    setTimeout(() => window.location.href = "", 2000);
                                </script>

                                <?php
                                # code...
                            }else{
                                $this->set_message('<div class="alert alert-danger"> Failed to Add record! </div>');
                            }
            
                          // $query2 = mysqli_query($connection, "INSERT INTO staff (staff_name, email, phone, position) VALUES('".$column[0]."', '".$column[1]."', '".$column[2]."', '".$column[3]."')");
                          
                        }
                    }

        }

    }



     //Inserting Record into the Database
    function insert_multiple_data($name, $email, $phone, $position){

        global $db;

        $query = "INSERT INTO app_biodata (staff_name, email, phone, position) VALUES('$name','$email','$phone','$position')";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{
            return false;
        }
    }




      //Upload Timetable
    public function upload_timetable(){
        
        global $db;

        if (isset($_POST['btn_upload_timetable'])) {

            $filename = $_FILES["file"]["tmp_name"];
                    $filePath = 'uploads/'.$filename;
                    $tmp_name = $_FILES['file']['tmp_name'];
                    move_uploaded_file($tmp_name, $filePath);

                    if ($_FILES["file"]["size"] > 0) {
                        
                        $file = fopen($filename, "r");
                        while (($column=fgetcsv($file, 1000, ',')) !==FALSE) {
            
                          //echo '<pre>'; print_r($column);


                            if ($this->insert_timetable($column[0], $column[1], $column[2], $column[3], $column[4])) {

                            $this->set_message('<div class="alert alert-success text-center"> Timetable Uploaded Successfully</div>');
                             ?>
                                <script>
                                    setTimeout(() => window.location.href = "", 2000);
                                </script>

                                <?php
                                # code...
                            }else{
                                $this->set_message('<div class="alert alert-danger"> Failed to Add record! </div>');
                            }
            
                          // $query2 = mysqli_query($connection, "INSERT INTO staff (staff_name, email, phone, position) VALUES('".$column[0]."', '".$column[1]."', '".$column[2]."', '".$column[3]."')");
                          
                        }
                    }

        }

    }


     //Inserting Record into the Database
    function insert_timetable($course, $semester, $level, $date, $time){

        global $db;

        $query = "INSERT INTO timetable (course, semester, level, date, time) VALUES('$course','$semester','$level','$date', '$time')";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{
            return false;
        }
    }


      //ALLOCATE 
    public function allocate_invigilator(){
        
        global $db;

        if (isset($_POST['btn_schedule'])) {

                    $noOfStaff = $_POST['staff'];

                    if (empty($noOfStaff)) {
                        // code...
                        return  $this->set_message('<div class="alert alert-danger text-center"> Please select number of Invigilators </div>');
                    }


                    if ($this->schedule($noOfStaff)) {

                         ?>

                      
                        <script>
                             setTimeout(() => document.getElementById('loader').style.display = "block", 1000)
                        </script>

                        <script>
                             setTimeout(() => document.getElementById('loader').style.display = "none", 8000)
                        </script>
                        <script>
                             setTimeout(() => document.getElementById('success').style.display = "block", 9000)
                        </script>
                    
                    
                        <?php
                        # code...
                    }else{
                        $this->set_message('<div class="alert alert-danger"> Failed to Add record! </div>');
                    }
                  
                }

        }


    public function schedule($noOfStaff){

        global $db;

        //TRUNCATE ALLOCATION TABLE
        $sql = "TRUNCATE TABLE `allocation`";
        $res = mysqli_query($db->connection, $sql);

        $staff = [];
        $timetable = [];
        $allocation = [];
        $staffArray = [];
        $currentStaff = [];
        $duplicate = [];
        $hallDuplicate = [];
        $hall =  array('ND-I', 'ND-II', 'HND-I', 'HND-II', 'SOFTWARE-LAB', 'HARDWARE-LAB' );

        //GET LIST OF ALL STAFF
        $staff_data = $this->get_staff();

        //GET ALL EXAMINATION TIME TABLE
        $timetable_data = $this->get_timetable();

         while ($row = mysqli_fetch_array($staff_data)) {
              array_push($staff, $row["staff_name"]);
        }

        while ($roww = mysqli_fetch_array($timetable_data)) {
              array_push($timetable, $roww["timetable_id"]);
        }

        shuffle($hall);
        shuffle($staff);

        for ($i=0; $i <sizeof($timetable); $i++) { 
            // code...
            $arrayDiff = array_diff($staff, $duplicate);
            shuffle($arrayDiff);
            $currentTime = $timetable[$i];
            if (sizeof($timetable) > sizeof($hall)) {
                array_push($hall, $hall[$i]);
                $currentHall = $hall[$i];
            }else{
                $currentHall = $hall[$i];
            }
            for ($j=0; $j <$noOfStaff; $j++) { 
                // code...
                if (is_null(isset($arrayDiff[$j]) ? $arrayDiff[$j] : null) || empty(isset($arrayDiff[$j]) ? $arrayDiff[$j] : null)) {
                    // code...
                    $duplicate = [];
                    $arrayDiff = array_diff($staff, $duplicate);
                    $currentStaff[] = $arrayDiff[$j];
                    array_push($staffArray, $currentStaff[$j]);
                    array_push($duplicate, $arrayDiff[$j]);
                }else{
                    $currentStaff[] = $arrayDiff[$j];
                    array_push($staffArray, $currentStaff[$j]);
                    array_push($duplicate, $arrayDiff[$j]);
                }
                    
                
            }
            $currentAllocation = $currentTime.'/'.$currentHall.'/'.json_encode($staffArray);
            array_push($allocation, $currentAllocation);
            $staffArray = [];
            $currentStaff = [];
}

  foreach ($allocation as $key => $value) {
    // code...
     $staff_list = explode('/', $value)[2];
     $staff_list = preg_replace('/["["]/', "", $staff_list);
     $staff_list = explode(']', $staff_list)[0];
     $hall_list = explode('/', $value)[1];
     $timetable_list = explode('/', $value)[0];
    $res = $this->allocate($timetable_list, $staff_list, $hall_list);
   }

   
        

             if ($res) {
            return true;
            # code...
                }else{

                    return false;
                }
                    
    }

    //Get all timetable for scheduling
    public function get_timetable(){
        global $db;
        $query = "SELECT * FROM timetable ";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }


     //Inserting Record into the Database
    public function allocate($timetable_id, $staff, $hall){

        global $db;


        $query = "INSERT INTO allocation (timetable_id, staff, hall) VALUES('$timetable_id','$staff','$hall')";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{
            return false;
        }
    }

    //Fetching Records from the Database
    public function get_staff(){

        global $db;
        $query = "SELECT * FROM staff";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }


    //Get all timetable for scheduling
    public function check_staff_validity($staff_id){
        global $db;
        $query = "SELECT * FROM timetable WHERE staff = '$staff_id' ";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }
    


     //Fetching Records from the Database
    public function fetch_timetable(){

        global $db;
        $query = "SELECT * FROM timetable WHERE time LIKE '8:30 AM'";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }


    //Fetching Records from the Database
    public function fetch_timetable_two(){

        global $db;
        $query = "SELECT * FROM timetable  WHERE time LIKE '11:00 AM'  ORDER BY date ASC";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }


      //Fetching Records from the Database
    public function fetch_allocation_one(){

        global $db;
        $query = "SELECT *, allocation.hall as 'HALL', allocation.staff as 'Invigilators' FROM allocation
        LEFT JOIN timetable ON timetable.timetable_id = allocation.timetable_id WHERE time LIKE '8:30 AM'";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }


    //Fetching Records from the Database
    public function fetch_allocation_two(){

        global $db;
        $query = "SELECT *, allocation.hall as 'HALL', allocation.staff as 'Invigilators' FROM allocation
        LEFT JOIN timetable ON timetable.timetable_id = allocation.timetable_id  WHERE time LIKE '11:00 AM'  ORDER BY date ASC";
        $result = mysqli_query($db->connection, $query);
        return $result;
    }





    //Updating record
    public function update(){

        global $db;

        if (isset($_POST['btn_update'])) {

            $id = $db->check($_POST['user_id']);
            $fname = $db->check($_POST['fname']);
            $username = $db->check($_POST['username']);
            $email = $db->check($_POST['email']);
            $phone = $db->check($_POST['phone']);
            $password = $db->check($_POST['password']);

            if ($this->update_record($id, $fname, $username, $email, $phone, $password)) {

                $this->set_message('<div class="alert alert-success"> Record Updated Successfully</div>');
                 ?>
                    <script>
                        setTimeout(() => window.location.href = "view_record.php", 2000);
                    </script>

                <?php
               
                # code...
            }else{

                $this->set_message('<div class="alert alert-danger"> Failed to update record! </div>');
            }
            # code...
        }

    }

    public function update_record($id, $fname, $username, $email, $phone, $password){

        global $db;
        $query = "UPDATE users SET fullname='$fname', username='$username', email='$email', phone='$phone', pass='$password' WHERE user_id='$id'";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{

            return false;
        }
    }


    public function set_message($msg){

        if (!empty($msg)) {

            $_SESSION['Message'] = $msg;
            # code...
        }else{
            $msg ="";
        }
    }

    public function display_message(){

        if (isset($_SESSION['Message'])) {

            echo $_SESSION['Message'];
            unset($_SESSION['Message']);
            # code...
        }
    }




     public function get_userID(){

        if (isset($_POST['btn_getID'])) {

            $_SESSION['userID'] = $_POST['btn_getID'];
            header("location: edit.php");

            # code...
        }
    }

public function delete_student() {
    global $db;

    if (isset($_POST['btn_delete_student'])) {
        $student_id = $_POST['student_id'];

        $query = "DELETE FROM students WHERE student_id='$student_id'";
        if (mysqli_query($db->connection, $query)) {
            $this->set_message('<div class="alert alert-success">Student deleted successfully.</div>');
        } else {
            $this->set_message('<div class="alert alert-danger">Failed to delete student.</div>');
        }
    }
}





    //Delete user record
    public function delete(){

        global $db;

        if (isset($_POST['btn_delete'])) {

            $id = $db->check($_POST['btn_delete']);

            if ($this->delete_record($id)) {

                $this->set_message('<div class="alert alert-success"> Record Deleted Successfully</div>');
                 ?>
                    <script>
                        setTimeout(() => window.location.href = "", 2000);
                    </script>

                <?php
               
                # code...
            }else{

                $this->set_message('<div class="alert alert-danger"> Failed to delete record! </div>');
            }
            # code...
        }

    }




    public function delete_record($id){

        global $db;
        $query = "DELETE FROM users WHERE user_id='$id'";
        $result = mysqli_query($db->connection, $query);

        if ($result) {

            return true;
            # code...
        }else{

            return false;
        }

    }


          //Admin Login
    public function admin_login(){
        

        if (isset($_POST['btn_admin_login'])) {


            $_SESSION['username'] = $this->check($_POST['username']);
            $password = $this->check($_POST['password']);
            

            if ($this->app_login($_SESSION['username'], $password)) {

                $this->set_message('<div class="alert alert-success text-center"> Login Successfully!</div>');
                    # code...
                    ?>
                 <script>
                        setTimeout(() => window.location.href = "Dashboard.php", 2000);
                    </script>


                <?php

            }else{
                $this->set_message('<div class="alert alert-danger text-center" id="msg"> Invalid Login Credentials! </div>');
                ?>
                 <script>
                        setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
                    </script>
                <?php
            }
        }

    }

    // Fetch student details by ID
public function get_student_by_id($student_id) {
    global $db;
    $query = "SELECT * FROM students WHERE student_id = '$student_id'";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_assoc($result);
}

// Update student details
public function update_student($student_id) {
    global $db;

    if (isset($_POST['btn_update_student'])) {
        $name = $db->check($_POST['name']);
        $email = $db->check($_POST['email']);
        $phone = $db->check($_POST['phone']);
        $department = $db->check($_POST['department']);
        $year_of_study = $db->check($_POST['year_of_study']);

        $query = "UPDATE students SET name='$name', email='$email', phone='$phone', reg_no='$department', year_of_study='$year_of_study' WHERE student_id='$student_id'";
        $result = mysqli_query($db->connection, $query);

        if ($result) {
             $this->set_message('<div class="alert alert-success text-center">Donor Updated Successfully</div>');
             echo '<script>setTimeout(() => window.location.href = "manage_donors.php", 2000);</script>';
        } else {
            $this->set_message('<div class="alert alert-danger">Failed to Update Student!</div>');
        }
    }
}


    public function get_students() {
        global $db;
        $query = "SELECT * FROM students ORDER BY student_id DESC"; // Adjust according to your table structure
        $result = mysqli_query($db->connection, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function get_seminar() {
        global $db;
        $query = "SELECT * FROM seminar ORDER BY seminar_id DESC"; // Adjust according to your table structure
        $result = mysqli_query($db->connection, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function get_students_to_assign_project() {
        global $db;
        
        // Adjust this query according to your actual table and column names
        $query = "
            SELECT s.*
            FROM students s
            LEFT JOIN projects p ON s.student_id = p.assigned_student
            WHERE p.assigned_student IS NULL
            ORDER BY s.student_id DESC";
        
        $result = mysqli_query($db->connection, $query);
        
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            // Handle query error
            return []; // Return an empty array on failure
        }
    }

    public function get_supervisors_with_student_count() {
    global $db;

    // Query to get hospital officers and the count of assigned donors
    $query = "
        SELECT s.staff_id, s.staff_name, s.email, s.phone, s.position, s.date_registered,
               COUNT(p.project_id) AS student_count
        FROM staff s
        LEFT JOIN projects p ON s.staff_id = p.assigned_supervisor
        GROUP BY s.staff_id, s.staff_name
        ORDER BY s.staff_name ASC";

    $result = mysqli_query($db->connection, $query);

    if ($result) {
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        return [];
    }
}


// Fetch students assigned to a given supervisor via projects.assigned_supervisor
public function get_assigned_students($supervisor_id) {
    // ensure int (avoid injection)
    $sid = intval($supervisor_id);

    $sql = "
      SELECT
        p.project_id,
        p.title,
        p.status,
        p.created_at,
        s.student_id,
        s.name,
        s.email,
        s.phone,
        s.reg_no,
        s.blood_group
      FROM projects p
      INNER JOIN students s ON p.assigned_student = s.student_id
      WHERE p.assigned_supervisor = {$sid}
      ORDER BY s.name ASC
    ";

    $res = mysqli_query($this->connection, $sql);

    if (!$res) {
        // optional: log mysqli_error($this->connection);
        return [];
    }

    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

// Every donor↔officer assignment (admin view of assigned_donors.php).
public function get_all_assignments() {
    global $db;
    $sql = "
      SELECT p.project_id, p.title, p.status, p.created_at,
             s.student_id, s.name, s.email, s.phone, s.reg_no, s.blood_group,
             st.staff_name AS officer_name
      FROM projects p
      INNER JOIN students s ON p.assigned_student = s.student_id
      LEFT JOIN staff st ON st.staff_id = p.assigned_supervisor
      ORDER BY s.name ASC";
    $res = mysqli_query($db->connection, $sql);
    return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}



// Fetch students assigned to a supervisor
// public function get_assigned_students($supervisor_id) {
//   global $db;
//   $query = "
//     SELECT s.student_id, s.name, s.reg_no, p.project_id, p.title
//     FROM students s
//     JOIN projects p ON s.student_id = p.assigned_student
//     WHERE p.assigned_supervisor = '$supervisor_id'
//   ";
//   $result = mysqli_query($db->connection, $query);
//   return mysqli_fetch_all($result, MYSQLI_ASSOC);
// }

// Post a notice
public function post_notice($supervisor_id, $title, $message) {
    global $db;
    $query = "INSERT INTO notices (supervisor_id, title, message) VALUES ('$supervisor_id', '$title', '$message')";
    return mysqli_query($db->connection, $query);
}

// Get all notices by supervisor
public function get_supervisor_notices($supervisor_id) {
    global $db;
    $query = "SELECT * FROM notices WHERE supervisor_id = '$supervisor_id' ORDER BY created_at DESC";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Optional: delete notice
public function delete_notice($notice_id) {
    global $db;
    $query = "DELETE FROM notices WHERE notice_id='$notice_id'";
    return mysqli_query($db->connection, $query);
}

// Get a single notice by ID
public function get_notice_by_id($notice_id) {
    global $db;
    $query = "SELECT * FROM notices WHERE notice_id = '$notice_id'";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_assoc($result);
}

// Get all notices posted by the supervisor of this student
public function get_student_notices($student_id) {
    global $db;

    $query = "
        SELECT n.title, n.message, n.created_at, s.staff_name
        FROM notices n
        JOIN staff s ON n.supervisor_id = s.staff_id
        JOIN projects p ON s.staff_id = p.assigned_supervisor
        WHERE p.assigned_student = '$student_id'
        ORDER BY n.created_at DESC
    ";

    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}



// Get all attendance records for a student
public function get_student_attendance($student_id) {
    global $db;
    $query = "
        SELECT a.attendance_date, a.status, p.title AS project_title, s.staff_name AS supervisor_name
        FROM attendance a
        JOIN projects p ON a.project_id = p.project_id
        JOIN staff s ON a.supervisor_id = s.staff_id
        WHERE a.student_id = '$student_id'
        ORDER BY a.attendance_date DESC
    ";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


// Get all seminars
public function get_all_seminars() {
    global $db;
    $query = "SELECT * FROM seminar ORDER BY seminar_date ASC, seminar_time ASC";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get the assigned supervisor and project for a student
public function get_student_supervisor($student_id) {
    global $db;
    $query = "
        SELECT st.staff_name, st.email, st.phone, st.position, p.title AS project_title
        FROM staff st
        JOIN projects p ON st.staff_id = p.assigned_supervisor
        WHERE p.assigned_student = '$student_id'
        LIMIT 1
    ";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_assoc($result);
}

// Save daily logbook entry
public function add_logbook_entry($student_id, $entry_date, $activities) {
    global $db;
    $student_id = mysqli_real_escape_string($db->connection, $student_id);
    $entry_date = mysqli_real_escape_string($db->connection, $entry_date);
    $activities = mysqli_real_escape_string($db->connection, $activities);

    $query = "
        INSERT INTO logbook_entries (student_id, entry_date, activities)
        VALUES ('$student_id', '$entry_date', '$activities')
    ";
    return mysqli_query($db->connection, $query);
}

// Fetch student logbook entries
public function get_student_logbook($student_id) {
    global $db;
    $query = "
        SELECT * FROM logbook_entries
        WHERE student_id = '$student_id'
        ORDER BY entry_date DESC
    ";
    return mysqli_query($db->connection, $query);
}


// Add weekly summary
public function add_weekly_summary($student_id, $week_start, $week_end, $summary) {
    global $db;
    $student_id = mysqli_real_escape_string($db->connection, $student_id);
    $week_start = mysqli_real_escape_string($db->connection, $week_start);
    $week_end   = mysqli_real_escape_string($db->connection, $week_end);
    $summary    = mysqli_real_escape_string($db->connection, $summary);

    $query = "
        INSERT INTO weekly_summaries (student_id, week_start, week_end, summary)
        VALUES ('$student_id', '$week_start', '$week_end', '$summary')
    ";
    return mysqli_query($db->connection, $query);
}

// Fetch student weekly summaries
public function get_student_weekly_summaries($student_id) {
    global $db;
    $query = "
        SELECT *
        FROM weekly_summaries
        WHERE student_id = '$student_id'
        ORDER BY week_start DESC
    ";
    return mysqli_query($db->connection, $query);
}

// Fetch all logbook entries that belong to students assigned to this supervisor
public function get_supervisor_logbook_entries($supervisor_id) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);

    $query = "
        SELECT lb.*, s.name AS student_name, s.reg_no, p.project_id, p.title AS project_title
        FROM logbook_entries lb
        JOIN students s ON s.student_id = lb.student_id
        JOIN projects p ON p.assigned_student = s.student_id
        WHERE p.assigned_supervisor = '$supervisor_id'
        ORDER BY lb.entry_date DESC, lb.created_at DESC
    ";
    $result = mysqli_query($db->connection, $query);
    $rows = [];
    if ($result) {
        while ($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

// Fetch logbook entries for a single student, but only if the student is assigned to this supervisor
public function get_student_logbook_for_supervisor($supervisor_id, $student_id) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);
    $student_id = mysqli_real_escape_string($db->connection, $student_id);

    $query = "
        SELECT lb.*, s.name AS student_name, s.reg_no, p.project_id, p.title AS project_title
        FROM logbook_entries lb
        JOIN students s ON s.student_id = lb.student_id
        JOIN projects p ON p.assigned_student = s.student_id
        WHERE p.assigned_supervisor = '$supervisor_id' 
          AND s.student_id = '$student_id'
        ORDER BY lb.entry_date DESC, lb.created_at DESC
    ";
    $result = mysqli_query($db->connection, $query);
    $rows = [];
    if ($result) {
        while ($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

// Supervisor reviews an entry: set status and comment
public function review_logbook_entry($log_id, $status, $comment, $supervisor_id) {
    global $db;
    $log_id = mysqli_real_escape_string($db->connection, $log_id);
    $status = mysqli_real_escape_string($db->connection, $status);
    $comment = mysqli_real_escape_string($db->connection, $comment);
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);

    // Optionally store which supervisor reviewed it, you can add reviewed_by column if preferred.
    $query = "
        UPDATE logbook_entries
        SET status = '$status',
            supervisor_comment = '$comment'
        WHERE log_id = '$log_id'
    ";
    return mysqli_query($db->connection, $query);
}


// Fetch weekly summaries from students assigned to this supervisor
public function get_supervisor_weekly_summaries($supervisor_id) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);

    $query = "
        SELECT ws.*, s.name AS student_name, s.reg_no, p.project_id, p.title AS project_title
        FROM weekly_summaries ws
        JOIN students s ON s.student_id = ws.student_id
        JOIN projects p ON p.assigned_student = s.student_id
        WHERE p.assigned_supervisor = '$supervisor_id'
        ORDER BY ws.week_start DESC, ws.created_at DESC
    ";
    $result = mysqli_query($db->connection, $query);
    $rows = [];
    if ($result) {
        while ($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

// Fetch weekly summaries for a single student only if assigned to this supervisor
public function get_student_weekly_for_supervisor($supervisor_id, $student_id) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);
    $student_id = mysqli_real_escape_string($db->connection, $student_id);

    $query = "
        SELECT ws.*, s.name AS student_name, s.reg_no, p.project_id, p.title AS project_title
        FROM weekly_summaries ws
        JOIN students s ON s.student_id = ws.student_id
        JOIN projects p ON p.assigned_student = s.student_id
        WHERE p.assigned_supervisor = '$supervisor_id'
          AND s.student_id = '$student_id'
        ORDER BY ws.week_start DESC, ws.created_at DESC
    ";
    $result = mysqli_query($db->connection, $query);
    $rows = [];
    if ($result) {
        while ($r = mysqli_fetch_assoc($result)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

// Supervisor reviews a weekly summary
public function review_weekly_summary($summary_id, $status, $comment, $supervisor_id) {
    global $db;
    $summary_id = mysqli_real_escape_string($db->connection, $summary_id);
    $status = mysqli_real_escape_string($db->connection, $status);
    $comment = mysqli_real_escape_string($db->connection, $comment);
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);

    $query = "
        UPDATE weekly_summaries
        SET status = '$status',
            supervisor_comment = '$comment'
        WHERE summary_id = '$summary_id'
    ";
    return mysqli_query($db->connection, $query);
}


// Insert a chat message
public function send_chat_message($supervisor_id, $student_id, $sender, $message) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);
    $student_id    = mysqli_real_escape_string($db->connection, $student_id);
    $sender        = mysqli_real_escape_string($db->connection, $sender); // 'supervisor' or 'student'
    $message       = mysqli_real_escape_string($db->connection, $message);

    $query = "
      INSERT INTO chat_messages (supervisor_id, student_id, sender, message)
      VALUES ('$supervisor_id', '$student_id', '$sender', '$message')
    ";
    return mysqli_query($db->connection, $query);
}

// Get chat history for supervisor <-> student
public function get_chat_history($supervisor_id, $student_id, $limit = 500) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);
    $student_id    = mysqli_real_escape_string($db->connection, $student_id);
    $limit = intval($limit);

    $query = "
      SELECT * FROM chat_messages
      WHERE supervisor_id = '$supervisor_id' AND student_id = '$student_id'
      ORDER BY created_at ASC
      LIMIT $limit
    ";
    $result = mysqli_query($db->connection, $query);
    $rows = [];
    if ($result) {
        while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
    }
    return $rows;
}

// Mark messages as read (for the recipient)
public function mark_messages_read($supervisor_id, $student_id, $recipient) {
    global $db;
    $supervisor_id = mysqli_real_escape_string($db->connection, $supervisor_id);
    $student_id    = mysqli_real_escape_string($db->connection, $student_id);
    // recipient is 'supervisor' or 'student' — we want to mark messages where sender != recipient
    $query = "
      UPDATE chat_messages
      SET is_read = 1
      WHERE supervisor_id = '$supervisor_id'
        AND student_id = '$student_id'
        AND sender != '" . mysqli_real_escape_string($db->connection, $recipient) . "'
    ";
    return mysqli_query($db->connection, $query);
}




// Check if attendance already marked for student
public function check_attendance_marked($student_id, $date) {
  global $db;
  $query = "SELECT * FROM attendance WHERE student_id = '$student_id' AND attendance_date = '$date'";
  $result = mysqli_query($db->connection, $query);
  return mysqli_num_rows($result) > 0;
}

// Mark attendance (ensures no duplicate for same day)
public function mark_attendance($supervisor_id, $student_id, $project_id, $date, $status) {
  global $db;
  $query = "
    INSERT INTO attendance (project_id, student_id, supervisor_id, attendance_date, status)
    VALUES ('$project_id', '$student_id', '$supervisor_id', '$date', '$status')
    ON DUPLICATE KEY UPDATE status = '$status'
  ";
  return mysqli_query($db->connection, $query);
}

    public function get_all_projects() {
        global $db;
        $query = "SELECT * FROM projects
                                LEFT JOIN students ON students.student_id = projects.assigned_student
                                LEFT JOIN staff ON staff.staff_id = projects.assigned_supervisor ORDER BY projects.project_id DESC"; // Adjust according to your table structure
        $result = mysqli_query($db->connection, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

        public function get_supervisors() {
            global $db;
            $query = "SELECT staff_id, staff_name FROM staff"; // Adjust according to your table structure
            $result = mysqli_query($db->connection, $query);
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }


        //Admin Login
     protected function app_login($a, $b){

        // Prepared statement + exact match. The old query used LIKE on both
        // USERNAME and PASSWORD, so a password of "%" matched every account
        // and any wildcard leaked in (BL-12 / BL-13). Look the user up by
        // username only, then verify the password (with legacy-plaintext
        // migration) in PHP.
        $data = null;
        if ($stmt = mysqli_prepare($this->connection,
                "SELECT * FROM login WHERE USERNAME = ? AND PW_STATUS = 'Active' LIMIT 1")) {
            mysqli_stmt_bind_param($stmt, "s", $a);
            mysqli_stmt_execute($stmt);
            $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }

        if ($data && $this->verify_password($b, $data['PASSWORD'], 'login', 'user_id', $data['user_id'])) {
               $_SESSION['user'] = $data["user_id"];
               $_SESSION['Active'] = 'Active';
               $_SESSION['role'] = 'admin';
               return true;
        }
        return false;

    }

    // ===================== BLOOD DONATION MATCHING (56-DAY RULE) =====================

    const DONATION_ELIGIBILITY_DAYS = 56;

    // Recipient blood group => compatible donor blood groups
    public function get_compatible_donor_types($recipient_blood_group) {
        $map = [
            'A+'  => ['A+', 'A-', 'O+', 'O-'],
            'A-'  => ['A-', 'O-'],
            'B+'  => ['B+', 'B-', 'O+', 'O-'],
            'B-'  => ['B-', 'O-'],
            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'AB-' => ['A-', 'B-', 'AB-', 'O-'],
            'O+'  => ['O+', 'O-'],
            'O-'  => ['O-'],
        ];
        $recipient_blood_group = strtoupper(trim($recipient_blood_group));
        return isset($map[$recipient_blood_group]) ? $map[$recipient_blood_group] : [];
    }

    // Is a donor eligible right now? (56 days since last donation, or never donated)
    public function is_donor_eligible($last_donation_date) {
        if (empty($last_donation_date)) return true;
        $last = new DateTime($last_donation_date);
        $today = new DateTime('today');
        $days_since = (int)$today->diff($last)->days;
        return $days_since >= self::DONATION_ELIGIBILITY_DAYS;
    }

    // Days remaining before a donor becomes eligible again (0 if already eligible)
    public function days_until_eligible($last_donation_date) {
        if (empty($last_donation_date)) return 0;
        $last = new DateTime($last_donation_date);
        $today = new DateTime('today');
        $days_since = (int)$today->diff($last)->days;
        $remaining = self::DONATION_ELIGIBILITY_DAYS - $days_since;
        return $remaining > 0 ? $remaining : 0;
    }

    // Find eligible, compatible donors for a given recipient blood group
    // Eligible, compatible donors for a recipient blood group.
    //
    // The whole reason this system has geo-location: blood is time-critical and
    // the donor has to physically reach the hospital, so when the request's
    // coordinates are known we compute each donor's distance (Haversine, in km)
    // and order NEAREST FIRST — that is the clinically useful ordering for an
    // officer working a critical request. Donors without coordinates are still
    // returned (they're valid donors), just sorted after the located ones.
    //
    // With no coordinates passed, behaviour is unchanged: order by longest time
    // since last donation.
    public function find_matching_donors($recipient_blood_group, $lat = null, $lng = null, $radius_km = null) {
        global $db;

        $compatible_types = $this->get_compatible_donor_types($recipient_blood_group);
        if (empty($compatible_types)) return [];

        $safe_types = array_map(function($t) use ($db) {
            return "'" . mysqli_real_escape_string($db->connection, $t) . "'";
        }, $compatible_types);
        $in_clause = implode(',', $safe_types);

        $has_geo = is_numeric($lat) && is_numeric($lng);
        if ($has_geo) {
            $lat = (float)$lat; $lng = (float)$lng;
            // 6371 = Earth radius in km.
            $distance_expr = "(6371 * ACOS(
                LEAST(1.0, COS(RADIANS($lat)) * COS(RADIANS(latitude)) *
                COS(RADIANS(longitude) - RADIANS($lng)) +
                SIN(RADIANS($lat)) * SIN(RADIANS(latitude)))))";
            $radius_clause = ($radius_km !== null && is_numeric($radius_km))
                ? " AND $distance_expr <= " . (float)$radius_km : "";
            $query = "
                SELECT student_id, name, email, phone, blood_group, last_donation_date,
                       latitude, longitude,
                       CASE WHEN last_donation_date IS NULL THEN 9999
                            ELSE DATEDIFF(CURDATE(), last_donation_date) END AS days_since_last_donation,
                       CASE WHEN latitude IS NULL OR longitude IS NULL THEN NULL
                            ELSE ROUND($distance_expr, 1) END AS distance_km
                FROM students
                WHERE blood_group IN ($in_clause)
                  AND (last_donation_date IS NULL
                       OR DATEDIFF(CURDATE(), last_donation_date) >= " . self::DONATION_ELIGIBILITY_DAYS . ")
                  $radius_clause
                ORDER BY (distance_km IS NULL), distance_km ASC, days_since_last_donation DESC
            ";
        } else {
            $query = "
                SELECT student_id, name, email, phone, blood_group, last_donation_date,
                       latitude, longitude,
                       CASE WHEN last_donation_date IS NULL THEN 9999
                            ELSE DATEDIFF(CURDATE(), last_donation_date) END AS days_since_last_donation,
                       NULL AS distance_km
                FROM students
                WHERE blood_group IN ($in_clause)
                HAVING last_donation_date IS NULL
                       OR days_since_last_donation >= " . self::DONATION_ELIGIBILITY_DAYS . "
                ORDER BY days_since_last_donation DESC
            ";
        }
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // Confirm a donor match: donor -> recipient/request, approved by officer.
    // Creates the donation record, resets the donor's 56-day clock,
    // updates blood_stock, and updates the blood_requests status.
    public function process_donation($donor_id, $recipient_id, $request_id, $officer_id, $blood_group, $units) {
        global $db;

        $donor_id     = intval($donor_id);
        $recipient_id = $recipient_id ? intval($recipient_id) : null;
        $request_id   = $request_id ? intval($request_id) : null;
        $officer_id   = intval($officer_id);
        $blood_group  = $db->check($blood_group);
        $units        = intval($units);

        // Re-check eligibility server-side (never trust the form alone)
        $check = mysqli_query($db->connection, "SELECT last_donation_date FROM students WHERE student_id = '$donor_id'");
        $donor_row = $check ? mysqli_fetch_assoc($check) : null;

        if (!$donor_row) {
            return ['success' => false, 'message' => 'Donor not found.'];
        }
        if (!$this->is_donor_eligible($donor_row['last_donation_date'])) {
            $wait = $this->days_until_eligible($donor_row['last_donation_date']);
            return ['success' => false, 'message' => "Donor is not yet eligible. $wait day(s) remaining of the 56-day window."];
        }

        mysqli_begin_transaction($db->connection);

        try {
            $today = date('Y-m-d');
            $recipient_sql = $recipient_id ? "'$recipient_id'" : 'NULL';
            $request_sql = $request_id ? "'$request_id'" : 'NULL';

            // 1. Insert donation record
            $query = "INSERT INTO donations (donor_id, recipient_id, request_id, officer_id, blood_group, units, donation_date, status)
                      VALUES ('$donor_id', $recipient_sql, $request_sql, '$officer_id', '$blood_group', '$units', '$today', 'Completed')";
            if (!mysqli_query($db->connection, $query)) throw new Exception(mysqli_error($db->connection));
            $donation_id = mysqli_insert_id($db->connection);

            // 2. Reset donor's 56-day clock
            $query = "UPDATE students SET last_donation_date = '$today' WHERE student_id = '$donor_id'";
            if (!mysqli_query($db->connection, $query)) throw new Exception(mysqli_error($db->connection));

            // 3. Add donated units to stock
            // NOTE: blood_stock keys on blood_type (see db/schema.sql). This used to say
            // blood_group, which is the column name manage_blood_stock.php never used.
            $query = "UPDATE blood_stock SET units_available = units_available + '$units' WHERE blood_type = '$blood_group'";
            if (!mysqli_query($db->connection, $query)) throw new Exception(mysqli_error($db->connection));

            // 4. If tied to a request, update its status and draw the units back down from stock
            if ($request_id) {
                $req_result = mysqli_query($db->connection, "SELECT units_needed FROM blood_requests WHERE request_id = '$request_id'");
                $req_row = $req_result ? mysqli_fetch_assoc($req_result) : null;

                if ($req_row) {
                    $new_status = ($units >= intval($req_row['units_needed'])) ? 'Fulfilled' : 'Approved';
                    $query = "UPDATE blood_requests SET status = '$new_status' WHERE request_id = '$request_id'";
                    if (!mysqli_query($db->connection, $query)) throw new Exception(mysqli_error($db->connection));
                }

                $query = "UPDATE blood_stock SET units_available = GREATEST(units_available - '$units', 0) WHERE blood_type = '$blood_group'";
                if (!mysqli_query($db->connection, $query)) throw new Exception(mysqli_error($db->connection));
            }

            // 5. Resolve any related stock alert now that stock improved (non-critical, don't throw)
            // stock_alerts joins blood_stock on blood_type and uses 'active'/'resolved'
            // (manage_blood_stock.php's vocabulary). This used to join on a stock_id
            // column that does not exist and look for status 'unresolved', so it
            // silently matched nothing and alerts were never cleared by a donation.
            $query = "UPDATE stock_alerts sa
                      JOIN blood_stock bs ON bs.blood_type = sa.blood_type
                      SET sa.status = 'resolved', sa.resolved_at = NOW()
                      WHERE bs.blood_type = '$blood_group' AND bs.units_available >= bs.low_stock_threshold AND sa.status = 'active'";
            mysqli_query($db->connection, $query);

            mysqli_commit($db->connection);
            return ['success' => true, 'message' => 'Donation recorded successfully.', 'donation_id' => $donation_id];

        } catch (Exception $e) {
            mysqli_rollback($db->connection);
            return ['success' => false, 'message' => 'Failed to record donation: ' . $e->getMessage()];
        }
    }

    // Donation history for a donor: "You donated X to Y"
    public function get_donor_history($donor_id) {
        global $db;
        $donor_id = intval($donor_id);
        $query = "
            SELECT d.donation_id, d.blood_group, d.units, d.donation_date, d.status,
                   r.recipient_id, r.name AS recipient_name,
                   br.patient_name, br.hospital_name
            FROM donations d
            LEFT JOIN recipients r ON r.recipient_id = d.recipient_id
            LEFT JOIN blood_requests br ON br.request_id = d.request_id
            WHERE d.donor_id = '$donor_id'
            ORDER BY d.donation_date DESC
        ";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // Donation history for a recipient: "You received X from Y"
    public function get_recipient_history($recipient_id) {
        global $db;
        $recipient_id = intval($recipient_id);
        $query = "
            SELECT d.donation_id, d.blood_group, d.units, d.donation_date, d.status,
                   s.student_id AS donor_id, s.name AS donor_name,
                   br.patient_name, br.hospital_name
            FROM donations d
            LEFT JOIN students s ON s.student_id = d.donor_id
            LEFT JOIN blood_requests br ON br.request_id = d.request_id
            WHERE d.recipient_id = '$recipient_id'
            ORDER BY d.donation_date DESC
        ";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // Open (Pending/Approved) blood requests, most urgent first, for the officer's queue
    public function get_open_blood_requests() {
        global $db;
        $query = "SELECT * FROM blood_requests WHERE status IN ('Pending','Approved') ORDER BY
                    FIELD(urgency_level,'Critical Emergency','Urgent','Normal'), created_at ASC";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // Get a single blood request by ID
    public function get_blood_request($request_id) {
        global $db;
        $request_id = intval($request_id);
        $query = "SELECT * FROM blood_requests WHERE request_id = '$request_id'";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    // ===================== END BLOOD DONATION MATCHING =====================

}


// ===================== ACCESS-CONTROL HELPERS (BL-25) =====================
// Call these at the very top of a page, before any HTML output, so the
// redirect header can be sent. They give every protected page a consistent
// guard instead of the ad-hoc (and sometimes missing) checks scattered around.

if (!function_exists('bl_require_login')) {
    function bl_require_login() {
        if (empty($_SESSION['role']) || empty($_SESSION['Active'])) {
            header('Location: user-login.php');
            exit;
        }
    }
}

if (!function_exists('bl_require_role')) {
    function bl_require_role($roles) {
        bl_require_login();
        $roles = (array)$roles;
        if (!in_array($_SESSION['role'], $roles, true)) {
            // Logged in but wrong role — send to the admin login as a safe default.
            header('Location: login.php');
            exit;
        }
    }
}

// ===================== CSRF PROTECTION (BL-14) =====================
// bl_csrf_field() prints a hidden input for forms; bl_csrf_check() verifies it
// on POST. Token lives in the session for the whole login.

if (!function_exists('bl_csrf_token')) {
    function bl_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('bl_csrf_field')) {
    function bl_csrf_field() {
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(bl_csrf_token()) . '">';
    }
}

if (!function_exists('bl_csrf_check')) {
    function bl_csrf_check() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $sent = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
            http_response_code(419);
            die('Invalid or expired form token. Please go back and try again.');
        }
    }
}

// ===================== GEOCODING (Phase 3, BL-05 / BL-15) =====================
// Address -> [lat, lng] via OpenStreetMap Nominatim. Free, no API key. This
// replaces the hardcoded Google Geocoding call. Nominatim's usage policy caps
// callers at ~1 request/second and REQUIRES a descriptive User-Agent — so this
// is only ever called once, at write time (registration / request creation),
// never in a loop on page load. Returns [null, null] on any failure so callers
// can store NULL coordinates and carry on.
if (!function_exists('bl_geocode')) {
    function bl_geocode($address) {
        $address = trim((string)$address);
        if ($address === '') return [null, null];

        // Bias results to Nigeria; callers pass a plain address.
        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ng&q='
             . urlencode($address . ', Nigeria');

        $ctx = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: BloodLink/1.0 (blood bank management system)\r\nAccept: application/json\r\n",
            'timeout' => 6,
        ]]);

        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) return [null, null];

        $data = json_decode($resp, true);
        if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
            return [(float)$data[0]['lat'], (float)$data[0]['lon']];
        }
        return [null, null];
    }
}

?>