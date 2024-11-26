<?php

//TODO:
// email exists check, now it simply checks if the whole row exists (to avoid re register on refresh page)
// add tracking system

class Event{ 

    private $settings_path= __DIR__ ."/settings.xml";
    private $program_path= __DIR__ ."/program.xml";
    private $settings=array();
    public $HTML;
    private $conn=false; 
    public $locale;
    
    //Registered users access
    private $validPasswords = array('accesosi');

     function __construct() {
        session_start();
        $this->loadSettings($this->settings_path);
        include_once(__DIR__."/html.php");
        $this->HTML=new html($this);
        $this->urlManager();
        
        //load locale
        include_once __DIR__."/locale.php";
     }

    function loadSettings($path){
        $xml = simplexml_load_file($path);
        $array = $xml;
        $this->settings=$array;
      
    }

    function title(){
        return $this->settings->title;
    }

    function form($action="Regístrat",$before="",$after=""){

        //check if form is sent
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["userregister"])){

            if ($this->processForm()){
               
                //ok
                echo '<div class="message ok">';
                echo '<h3>'.$this->settings->messages->ok.'</h3>';
                echo '<a class="btn" href="/ics" target="_blank">'.$this->__("calendar").'</a>';
                echo '</div>';
            } else {
                //user exists
                echo '<div class="message ko"><h3>'.$this->settings->messages->ko.'</h3></div>';
            }

        } else {
            if ($this->settings->event->embed !=""){
                //if it has an streaming url
                if ($this->hasstarted()){
                    //event is between the morning of the event and the end
                    echo '<div class="form inlineform" >';
                    echo '<form method="POST" action="stream" >';
                    echo '<div class="field">';
                    echo '<label for="emailviewer">'.$this->__("registeredmail").'</label>';
                    echo '<input id="emailviewer" name="emailviewer" type="email"><br>';
                    echo '</div>';
                    echo '<input type="submit" class="btn btn-big" value="'.$this->__("continuar").'">';
                    echo '<input type="hidden" name="viewerlogin" value="viewerlogin">';
                   
                    echo '</form>';

                    echo '</div>';

                    echo '<br><br><div class="field">';
                    echo '<p>'.$this->__("ifnoremember").'</p>';
                    echo '</div>';

                    //echo '<a href="stream" class="btn btn-big">'.$this->__("entrar").'</a>';
                    $this->registerform($action,$before,$after);
                } else {
                    //not yet started
                    $this->registerform($action,$before,$after);
                }
            } else {
                //event started but it's IRL
                $this->registerform($action,$before,$after);
            }
        }
    }

    function registerform($action="Regístrat",$before="",$after=""){
        //check if we are in event time or not
        $destination="#";
        if ($this->hasstarted()){
            $destination="stream";
        }
        echo '<div class="form registerform"><form action="'.$destination.'" method="post">'.$before; //this line starts the form
            foreach($this->settings->fields->field as $f)
            {   
                echo '<div class="field '.$f->type.'">';
                $required = '';
                $req='';
                if($f->attributes()['required'] == 'true') {
                    $required = 'required';
                    $req='*';
                }
        
                echo '<label for="'.(string)$f->name.'">'.(string)$f->label.'<span class="asterisk">'.$req.'</span></label>';
        
                if($f->type == 'text' || $f->type == 'email' || $f->type == 'phone') {
                    echo '<input type="'.$f->type.'" id="'.$f->name.'" name="'.$f->name.'" '.$required.'><br>';
                } elseif($f->type == 'checkbox') {
                    echo '<input type="checkbox" id="'.$f->name.'" name="'.$f->name.'" '.$required.'><br>';
                }
                echo '</div>';
            }
            echo '<div class="field submit">';
            echo '<input  type="hidden" name="userregister" value="1" >'; 
            echo '<input class="btn" type="submit" value="'.$this->__("continuar").'">'; 
            echo '</div>';
            echo '</form>'.$after.'</div>'; 

            echo '<a href="#" class="btn btn-big openform">'.$action.'</a>';
    }

    function program(){
        $xml = simplexml_load_file($this->program_path);
        echo '<div class="program">'; 
        foreach($xml->e as $e){
            echo '<div class="e">';
            echo '<div class="time">'.$e->time.'</div>';
            echo '<div class="cont">';
            echo '<div class="title">';
            if ($e->pretitle){
                echo '<h3>'.$e->pretitle.'</h3>';
            }
            echo '<h4>'.$e->title.'</h4>';
            echo '</div>';
            echo '<div class="txt">'.$e->body.'</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /***DB***/
    private function db(){
        if ($this->conn) {
            mysqli_close($this->conn);
        }
        $this->conn = mysqli_connect($this->settings->database->host, $this->settings->database->user, $this->settings->database->password, $this->settings->database->dbname);
        return $this->conn;
     
    }

    private function processForm(){
       
        $fields=array();
       
        //prepare vars from settings
        foreach($this->settings->fields->field as $f)
        {      
            $name=$f->name->__toString();
            if (isset($_POST[$name])){
                $fields[$name]=$_POST[$name];
            }
        }

        $this->createTableIfnotExists($this->settings->database->table->__toString(),$this->settings->fields->field);
        
        //INSERT user
        $query='INSERT INTO '.$this->settings->database->table.' ( ';
            foreach($fields as $key=>$value){
                $query.=$key.',';
    
            }
        $query=substr($query, 0, -1);
        $query.=') VALUES (';
            foreach($fields as $key=>$value){
                $query.='"'.$value.'",';
            }
            $query=substr($query, 0, -1);
            $query.=	')';
        
        
        //check if it's already inserted (page refresh)
            
        if (!$this->getUserId($fields["email"])){
        //if (!$this->rowExists($fields)){
            $res=mysqli_query($this->db(),$query);
            
            //send an email to the user with the OK text
            if (isset($fields["email"])){
                $subject=$this->__("registerok");
                $body=$this->settings->messages->ok->__toString();
                $dest=$fields["email"];
                $sent=$this->sendMail($subject,$dest,$body);
            }
        } else {
            return false;
        }

        return true;
    }

    private function emailexists($email){
        if ( $this->rowExists(["email"=>$email])){
            return true;
        }
        return false;
    }

    
    
    public function UserTrack($userid)
    {
        $userid=(int)$userid;
    
        // Prepare the table name dynamically
        $table = $this->settings->database->table . '_tracking';
    
        // Cast $userid to an integer to ensure safety
        $userid = (int)$userid;
    
        // Insert a new row into the tracking table
        $insertQuery = "INSERT INTO $table (userid, visionado) VALUES ($userid, 1)";
    
        if (!mysqli_query($this->db(), $insertQuery)) {
            // Log or handle the error
            error_log("Error inserting new record: " . mysqli_error($this->db()));
        } else {
            // Optionally log success
            error_log("User tracking initialized successfully for user ID $userid.");
        }
    }
    

    private function rowExists($data) {
        $query = "SELECT * FROM ".$this->settings->database->table->__toString()." WHERE ";
        $conditions = [];
    
        foreach ($data as $column => $value) {
            $conditions[] = "$column = '" . $this->db()->real_escape_string($value). "'";
        }
    
        $query .= implode(' AND ', $conditions);

        $result = $this->db()->query($query);
    
        return ($result && $result->num_rows > 0);
    }

    function getUserId($email) {
        // Sanitize email input to prevent SQL injection
        $email = mysqli_real_escape_string($this->db(), $email);
    
        // Prepare and execute the query
        $query = "SELECT id FROM " . $this->settings->database->table . " WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($this->db(), $query);
    
        // Check if a row was returned
        if ($result && mysqli_num_rows($result) > 0) {
            // Fetch the row as an associative array
            $row = mysqli_fetch_assoc($result);
            return (int)$row['id']; // Return the userId as an integer
        } else {
            // Return null if no user was found
            return null;
        }
    }

    function createTableIfnotExists($tableName,$fields) {
        try {
            // Check if the table exists
            $check = mysqli_query($this->db(), "SELECT 1 FROM `$tableName` LIMIT 1");
            return true; // Table exists
        } catch (mysqli_sql_exception $e) {
            // Table does not exist, so we proceed to create it
            $query = "CREATE TABLE IF NOT EXISTS `$tableName` (id INT AUTO_INCREMENT PRIMARY KEY";
    
            // Loop over the fields
            foreach ($fields as $field) {   
                $fieldName = $field["name"];
                $type = 'VARCHAR(255)'; // Default data type
    
                if ($field["type"] == 'email' || $field["type"] == 'textarea') {
                    $type = 'TEXT';
                } elseif ($field["type"] == 'phone') {
                    $type = 'VARCHAR(30)';
                } elseif ($field["type"] == 'number') {
                    $type = 'INT';
               
                } elseif ($field["type"] == 'createdon') {
                    $type = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                }
    
                $query .= ", `$fieldName` $type";
            }
    
            $query .= ")"; // Close the CREATE TABLE statement
    
            // Execute the query to create the table
            if (!mysqli_query($this->db(), $query)) {
                throw new Exception('Could not create table: ' . mysqli_error($this->db()));
            }
    
            return false; // Table was created
        }
    }
    

    public function ical(){
        $event=$this->settings->event;
        $ical=$this->createICS($event);

        header("Content-Type: text/plain");  // Use text/calendar if you want to open it directly in calendar
        header('Content-Disposition: attachment; filename="event.ics"');
        //header("Content-Length: " . strlen($data));

        echo $ical;
    }

    function createICS($event) {
        $dateStart = DateTime::createFromFormat('Y/m/d G:i', $event->datestart);
        
        // Adjust for timezone if needed
        $dateStart->setTimezone(new DateTimeZone('Europe/Madrid')); 
    
        $dateStart = new DateTime($event->datestart, new DateTimeZone('Europe/Madrid'));
        $dateEnd = new DateTime($event->dateend, new DateTimeZone('Europe/Madrid'));

        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\r\n";
        $icsContent .= "TZID:Europe/Madrid\r\n";
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . md5(uniqid(mt_rand(), true)) . "@example.com\r\n";
        $icsContent .= "DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z\r\n";
        $icsContent .= "DTSTART:" . $dateStart->format('Ymd\THis') . "\r\n";
        $icsContent .= "DTEND:" . $dateEnd->format('Ymd\THis') . "\r\n";
        $icsContent .= "LOCATION:" . $event->city . "\, " . $event->venue. "\, " . $event->adress . "\r\n";
        $icsContent .= "SUMMARY:" . $this->settings->title . "\r\n";
    
        //Alarm trigger for 1 day before
        $icsContent .= "BEGIN:VALARM\r\n";
        $icsContent .= "TRIGGER:-P1D\r\n";
        $icsContent .= "ACTION:DISPLAY\r\n";
        $icsContent .= "DESCRIPTION:Reminder\r\n";
        $icsContent .= "END:VALARM\r\n";
    
        //Alarm trigger for 2 hours before
        $icsContent .= "BEGIN:VALARM\r\n";
        $icsContent .= "TRIGGER:-PT2H\r\n";
        $icsContent .= "ACTION:DISPLAY\r\n";
        $icsContent .= "DESCRIPTION:Reminder\r\n";
        $icsContent .= "END:VALARM\r\n";
    
        $icsContent .= "END:VEVENT\r\n";
        $icsContent .= "END:VCALENDAR\r\n";
    
        return $icsContent;
    }

    public function viewerlogged(){
       
        if (isset($_SESSION["viewerlogged"])){
            return true;
        }
        return false;
    }

    public function streampage(){
        $this->HTML->head();
        include_once __DIR__."/locale.php";
        
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["userregister"])){
            if ($this->processForm()){
                $_SESSION["viewerlogged"]=true;
                $userid=$this->getUserId($_POST["email"]);
                //setcookie("viewerid",$userid, time()+3600*5);
                $_SESSION["viewerid"]=$userid;
            } else {
                echo '<div class="c"><div class="message ko"><h3>'.$this->settings->messages->ko.'</h3></div></div>';
                unset($_SESSION["viewerlogged"]);
            }
        }

        if (isset($_POST["viewerlogin"])){
            //check if email is on the DB
            
            if ($this->emailexists($_POST["emailviewer"])){
                $_SESSION["viewerlogged"]=true;
                $userid=$this->getUserId($_POST["emailviewer"]);
                $_SESSION["viewerid"]=$userid;
                //setcookie("viewerid",$userid, time()+3600*5);
            } else {
                echo '<div class="c"><div class="message ko"><h3>'.$this->__("emailnotfound").'</h3></div></div>';
                unset($_SESSION["viewerlogged"]);
            }
        }

        $haslogged=$this->viewerlogged();
      
        if ($haslogged){
            $fields=[["name"=>"visionado","type"=>"number"],["name"=>"userid","type"=>"int"],["name"=>"createdon","type"=>"createdon"]];
            $this->createTableIfnotExists($this->settings->database->table."_tracking",$fields);
            $this->UserTrack($_SESSION["viewerid"]);
            echo '<div class="theater">';
            echo '<div class="c embed">';
            echo '<iframe width="1236" height="695" src="'.$this->settings->event->embed.'" title="'.$this->settings->title.'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
            echo '<div></div>';
            echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>';
            echo '<script src="'.$this->HTML->assets.'js/stream.js" ></script>';
        } else {
            //show form with actions;
            echo '<div class="container c">';
            echo '<div class="intro center">';
            echo '<p>Para poder acceder necesitas registrarte.</p>';
            $this->registerform($this->__("registrar"));
            //$this->form($action=$this->__("registrar"));
            echo '<div></div>';
        }
  

        $this->HTML->bottom();
    }

    // Check if current date (ignoring time) is between datestart (without time) and dateend
    public function hasstarted() {
        $event = $this->settings->event;
        $datestart = (new DateTime($event->datestart))->setTime(0, 0, 0); // Strip time from datestart
        $dateend = new DateTime($event->dateend); // Keep full dateend (includes time)
        $currentDate = (new DateTime())->setTime(0, 0, 0); // Current date without time

        return $currentDate >= $datestart && $currentDate <= $dateend;
    }

    /***URL MANAGEMENT***/

    function urlManager(){
        // Get the request URI
        $url = $_SERVER['REQUEST_URI']; 
        
        // Remove the subfolder path (RewriteBase)
        $basePath = dirname($_SERVER['SCRIPT_NAME']); // e.g., /ivhees
        if (strpos($url, $basePath) === 0) {
            $url = substr($url, strlen($basePath));
        }
    
        // Trim leading/trailing slashes and split the URL into parts
        $url = trim($url, "/");
        $parts = explode("/", $url);
    
        // Debugging: Dump parts for testing
       
    
        // Check if the last part of the URL is valid
        if (!empty($parts[0])) {
            switch($parts[0]) {
                case "ics":
                    $this->ical();
                    die();
                    break;
                case "stream":
                    $this->streampage();
                    die();
                    break;
            }
        }
    }
    

    /**ADMIN*** */

    function admin(){
        $this->showregistered();

    }

    public function showregistered()
    {
        $count=0;
        $table=$this->settings->database->table->__toString();
        $query="SELECT * FROM ".$table;
        $result = mysqli_query($this->db(), $query);

        $H='';

        $H.= '<table border="0">';
      
        // Header
        $H.= '<tr>';
        foreach($this->settings->fields->field as $field)
        {
            $H.= '<th>'.htmlspecialchars($field->label). '</th>';
        }
        $H.= '</tr>';

        // Data
        while($row = mysqli_fetch_assoc($result)) {
            $count++;
            $H.= '<tr>';
            foreach($this->settings->fields->field as $field)
            {
                $name = $field->name->__toString();
                if(isset($row[$name])) {
                    $H.= '<td>'. htmlspecialchars($row[$name]). '</td>';
                }
                else {
                    $H.= '<td></td>';
                }
            }
            $H.= '</tr>';
        }

        $H.= '</table>';

        echo '<h3 class="">#N registrados: <b>'.$count.'</b></h3>';
        echo $H;
    }

    
    function passwordProtect() {
        if (isset($_POST['password'])){
            $password=$_POST['password'];
        }
        
        
        if(!isset($password)) {
            //Ask the user for a password if one isn't entered
            echo "Please enter a password to view this page.";
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Password Protected Page</title>
            </head>
            <body>
                <form method="post" action="">
                    <label for="password">Enter Password:</label><br>
                    <input type="password" id="password" name="password"><br>
                    <input type="submit" value="Submit">
                </form>
            </body>
            </html>';
            die();
        }

        //Check to see if the password is in our array of valid passwords
        if(in_array($password, $this->validPasswords)) {
            return true;
        } else {
            echo "Incorrect password. Please try again.";
            die();
        }   
    }



    /*****HELPERS******/

    function sendMail($subject,$dest,$body){
        //The url you wish to send the POST request to
        $url = "https://bmc-formacion.com/APImail.php";
        //The data you want to send via POST
        $fields = [
            'subject'  => $subject,
            'to' => $dest,
            'body'  => $body
        ];
        //url-ify the data for the POST
        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch);
        return $result;
      }

    function var_dump($var){
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }

    function getUrl(){
        return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /***** locale *****/
    function __($string){
        return $this->locale[$this->settings->lang->__toString()][$string];
    }
}


