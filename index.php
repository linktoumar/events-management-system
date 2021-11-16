<?php

require_once('Database.php');

class Events{
    private $db;

    function __construct(){
        $instance = Database::getInstance();
        $this->db = $instance->getConnection();
    }

    public function importEvents($fileName){
        $events = file_get_contents($fileName);
        try{
            foreach(json_decode($events) as $event){
                if(!is_object($event)){continue;} //if not an object continue
                $timeZone = (VersionComparison::compareVersion($event->version) === -1) ? 'Europe/Berlin' : 'UTC';
                $eventDate = date('Y-m-d h:i:s', strtotime($event->event_date." ".$timeZone)); //converting to UTC for saving in database.
                $event->prev_event_date = $event->event_date;
                $event->time_zone = $timeZone;
                $event->event_date = $eventDate;
    
                $insertSQL = "INSERT IGNORE INTO events (participation_id, employee_name, employee_mail, event_id, event_name, participation_fee, event_date, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
                $stmt = $this->db->prepare($insertSQL);
                $stmt->execute([$event->participation_id, $event->employee_name, $event->employee_mail, $event->event_id, $event->event_name, $event->participation_fee, $event->event_date, $event->version]);
                print_r($event);
            }
        }catch(Exception $e){
            echo $e->getMessage();
            exit(0);
        }
        echo "Import completed!";
    }

    public function getEvents($employeeName, $eventName, $date){
        $events = [];
        $sql = "select * from events where deleted = 0";
        $parameterizedArray = [];
        if(!empty($employeeName)){
            $sql .= " and employee_name like ?";
            $parameterizedArray[] = '%'.$employeeName.'%';
        }
        if(!empty($eventName)){
            $sql .= " and event_name like ?";
            $parameterizedArray[] = '%'.$eventName.'%';
        }
        if(!empty($date)){
            $sql .= " and DATE(event_date) = ? ";
            $parameterizedArray[] = $date;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameterizedArray);
        
        $header = ['Participation ID', 'Employee Name', 'Employee Mail', 'Event ID', 'Event Name', 'Participation Fee', 'Date'];
        $totalFee = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $event_date = $row['event_date']; //date can be converted back to user timezone
            $events[] = [
                $row['participation_id'],
                $row['employee_name'],
                $row['employee_mail'],
                $row['event_id'],
                $row['event_name'],
                $row['participation_fee'],
                $event_date
            ];
            $totalFee += (float)$row['participation_fee'];
        }

        return ['header' => $header, 'events' => $events, 'totalFee' => number_format($totalFee,2)];
    }
}

$Obj = new Events();

if($_GET['action'] && strtolower($_GET['action']) === 'getevents'){
    echo json_encode($Obj->getEvents($_GET['employee_name'], $_GET['event_name'], $_GET['date']));
    exit(0);
}

//read json file
if($_GET['action'] && strtolower($_GET['action']) === 'importevents' 
    && ($_GET['fileName'] && file_exists('files/'.$_GET['fileName']))){
    if(pathinfo('files/'.$_GET['fileName'], PATHINFO_EXTENSION) !== 'json'){
        echo "Not a valid JSON file!";
    }else{
        $Obj->importEvents('files/'.$_GET['fileName']);
    }
    exit(0);
}
//print filters
//added bootstrap CDN for table
echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">';
$filter_html = "<fieldset>
    <legend>Filters</legend>
    <form action='javascript:void(0)'>
        <div style='display: inline-block;'>&nbsp;&nbsp;
            <label for='employee_name'>Employee Name</label>
            <input type='text' name='employee_name' id='employee_name'>
        </div><div style='display: inline-block;'>&nbsp;&nbsp;
            <label for='event_name'>Event Name</label>
            <input type='text' name='event_name' id='event_name'>
        </div><div style='display: inline-block;'>&nbsp;&nbsp;
            <label for='date'>Date</label>
            <input type='date' name='date' id='date'>
        </div><div style='display: inline-block;'>&nbsp;&nbsp;
            <button id='search' name='search'>Search</button>
        </div>
    </form>
</fieldset>
<div id='data'></div>";
echo $filter_html;

echo <<<SCRIPT
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function(){
        $('#search').on('click', function(){
            var formData = $('form').serialize();
            $.get('index.php?action=getEvents', formData, function(response){
                $('#data').html(createTableFromData(JSON.parse(response)));
            });
        });
    });
    var createTableFromData = function(data){
        console.log("response",data);
        var html = "<table class='table table-stripped'><thead><tr>";
        $.each(data.header, function(i,v){
            html += "<th>"+v+"</th>";
        });
        html += "</tr></thead><tbody>";
        //print table rows
        
        $.each(data.events, function(i,event){
            html += "<tr>";
            $.each(event, function(j,v){
                html += "<td>"+v+"</td>";
            });
            html += "</tr>";
        });
        if($.isEmptyObject(data.events)){
            html += "<tr><td colspan='7'> No Data!</td></tr>";
        }else{
            html += "<tr style='font-weight:bold;'><td>Total Fee</td><td colspan='4''>&nbsp;</td><td>"+ data.totalFee+"</td><td>&nbsp;</td></tr>";
        }
        html += "</tbody></table>";
        return html;
    }
</script>
SCRIPT;

class VersionComparison{
    public static function compareVersion($version){
        $versionToCompare = '1.0.17+60';
        //using PHP native function
        return version_compare($version, $versionToCompare);
    }
}

?>