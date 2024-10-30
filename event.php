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
    
    //Registered users access
    private $validPasswords = array('accesosi');

     function __construct() {
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

    function form($label="Regístrat",$before="",$after=""){

        //check if form is sent
        if($_SERVER['REQUEST_METHOD'] == 'POST'){

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

            echo $before;

            echo '<form action="#" method="post">'; //this line starts the form
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
            echo '<input class="btn" type="submit" value="'.$label.'">'; //this line creates a submit button
            echo '</div>';
            echo '</form>'; //this line ends the form

            echo $after;
        }
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
        if (!$this->conn){
            $this->conn = mysqli_connect($this->settings->database->host, $this->settings->database->user, $this->settings->database->password, $this->settings->database->dbname);
        } 
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

        $this->createTableIfnotExists($this->settings->database->table->__toString());
        
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
        if (!$this->rowExists($fields)){
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

    function createTableIfnotExists($tableName){
        
        $check = mysqli_query($this->db(),'select 1 from $tableName LIMIT 1');

        if($check !== FALSE)
        {
            //DO SOMETHING! IT EXISTS!
            return true;
        }
        else
        {
            //does not exists
            $query = "CREATE TABLE IF NOT EXISTS $tableName (id INT AUTO_INCREMENT PRIMARY KEY";

            // Loop over the fields
            foreach($this->settings->fields->field as $field) 
            {   
                $fieldName = $field->name;
                $type = 'varchar(255)'; // Default data type

                if($field->type == 'email' || $field->type == 'textarea') 
                {
                    $type = 'text';
                } 
                elseif($field->type == 'phone') 
                {
                    $type = 'varchar(30)';
                }

                $query .= ", `$fieldName` $type";
            }

            $query .= ")";
        
            if(!mysqli_query($this->db(),$query))
            {
                throw new Exception('Could not create table: ' . $db->error);
            }
            return false;
        }
    }

    public function ical(){
        $event=$this->settings->event;
        $ical=$this->createICS($event);
        //$this->var_dump($event);
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

    /***URL MANAGEMENT***/

    function urlManager(){
        $url = $_SERVER['REQUEST_URI']; 
        $parts = explode("/", $url);
        //$this->var_dump($parts);

        if (strlen($parts[1])>0){
            switch($parts[1]){
                case "ics":
                    $this->ical();
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

