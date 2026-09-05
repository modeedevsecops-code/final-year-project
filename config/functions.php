<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
        $year_of_study = $db->check($_POST['year_of_study']);
        $address = $db->check($_POST['address']);

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

// Inserting Record into the Database
function insert_student($name, $email, $phone, $department, $year_of_study, $password, $address) {
    global $db;

    // Geocode the address into latitude/longitude
    $api_key = 'AIzaSyA23U0CLVVz5UBNZxiVjDBRS46zGE1C3HE';
    $geo_url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address . ', Nigeria') . "&key={$api_key}";

    $latitude = null;
    $longitude = null;

    $geo_response = @file_get_contents($geo_url);
    if ($geo_response !== false) {
        $geo_data = json_decode($geo_response, true);
        if ($geo_data['status'] === 'OK' && isset($geo_data['results'][0])) {
            $latitude = $geo_data['results'][0]['geometry']['location']['lat'];
            $longitude = $geo_data['results'][0]['geometry']['location']['lng'];
        }
    }

    $lat_val = $latitude !== null ? $latitude : 'NULL';
    $lng_val = $longitude !== null ? $longitude : 'NULL';

    $query = "INSERT INTO students (name, email, phone, reg_no, year_of_study, password, address, latitude, longitude) 
              VALUES ('$name', '$email', '$phone', '$department', '$year_of_study', '$password', '$address', $lat_val, $lng_val)";
    $result = mysqli_query($db->connection, $query);

    if ($result) {
        return true;
    } else {
        return false;
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

public function user_login() {

  if (isset($_POST['btn_user_login'])) {

    $role = $this->check($_POST['role']);

    if ($role == 'student') {
      // student login via reg_no only
      $reg_no = $this->check($_POST['reg_no']);
      $password = $_POST['student_password'];

      $query = "SELECT * FROM students WHERE reg_no = '$reg_no' AND password ='$password' LIMIT 1";
      $result = mysqli_query($this->connection, $query);

      if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

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
        $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid Registration Number!</div>');
?>
        <script>
          setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
        </script>
<?php
      }
    }

    elseif ($role == 'recipient') {
            // recipient login check
            $reg_no = $this->check($_POST['recipient_email']);
            $password = $_POST['recipient_password'];

            $query = "SELECT * FROM recipients WHERE email = '$reg_no' OR recipient_id = '$reg_no' LIMIT 1";
            $result = mysqli_query($this->connection, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {
                    $_SESSION['Active'] = 'Active';
                    $_SESSION['role'] = 'recipient';
                    $_SESSION['user_id'] = $user['recipient_id'];
                    $_SESSION['name'] = $user['name'];

                    $this->set_message('<div class="alert alert-success text-center">Login Successful!</div>');
                    ?>
                    <script>
                    setTimeout(() => window.location.href = "recipient_dashboard.php", 1500);
                    </script>
                    <?php
                } else {
                    $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid Password</div>');
                    ?>
                    <script>
                    setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
                    </script>
                    <?php
                }
            } else {
                $this->set_message('<div class="alert alert-danger text-center" id="msg">Recipient not found</div>');
                ?>
                <script>
                setTimeout(() => document.getElementById('msg').style.display = "none", 2000);
                </script>
                <?php
            }
        }
        
    elseif ($role == 'supervisor') {
      // supervisor login via email + password
      $email = $this->check($_POST['email']);
      $password = $this->check($_POST['password']);
      $query = "SELECT * FROM staff WHERE email = '$email' AND password = '$password' LIMIT 1"; 
      // (for now, password = phone — replace later with real password column)
      $result = mysqli_query($this->connection, $query);

      if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

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
        s.year_of_study
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

        $query = "SELECT * FROM login WHERE USERNAME LIKE '$a' AND PASSWORD LIKE '$b' And PW_STATUS LIKE 'Active'";
        $result = mysqli_query($this->connection, $query);
        $data = mysqli_fetch_assoc($result);

        if (mysqli_num_rows($result) > 0) {

               $_SESSION['user'] = $data["user_id"];
               $_SESSION['Active'] = 'Active';
               $_SESSION['role'] = 'admin';

             
            

            return true;
            # code...
        }else{
            return false;
        }

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
    public function find_matching_donors($recipient_blood_group) {
        global $db;

        $compatible_types = $this->get_compatible_donor_types($recipient_blood_group);
        if (empty($compatible_types)) return [];

        $safe_types = array_map(function($t) use ($db) {
            return "'" . mysqli_real_escape_string($db->connection, $t) . "'";
        }, $compatible_types);
        $in_clause = implode(',', $safe_types);

        $query = "
            SELECT student_id, name, email, phone, blood_group, last_donation_date,
                   CASE
                     WHEN last_donation_date IS NULL THEN 9999
                     ELSE DATEDIFF(CURDATE(), last_donation_date)
                   END AS days_since_last_donation
            FROM students
            WHERE blood_group IN ($in_clause)
            HAVING last_donation_date IS NULL
                   OR days_since_last_donation >= " . self::DONATION_ELIGIBILITY_DAYS . "
            ORDER BY days_since_last_donation DESC
        ";
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
            $query = "UPDATE blood_stock SET units_available = units_available + '$units' WHERE blood_group = '$blood_group'";
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

                $query = "UPDATE blood_stock SET units_available = GREATEST(units_available - '$units', 0) WHERE blood_group = '$blood_group'";
                if (!mysqli_query($db->connection, $query)) throw new Exception(mysqli_error($db->connection));
            }

            // 5. Resolve any related stock alert now that stock improved (non-critical, don't throw)
            $query = "UPDATE stock_alerts sa
                      JOIN blood_stock bs ON bs.stock_id = sa.stock_id
                      SET sa.status = 'resolved'
                      WHERE bs.blood_group = '$blood_group' AND bs.units_available > bs.low_stock_threshold AND sa.status = 'unresolved'";
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


?>