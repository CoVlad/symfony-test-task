<?php
$login=$_POST["log"];
$password=$_POST["pwd"];



//define DB
define('HOST', 'vikamade.mysql.ukraine.com.ua');
define('USER', 'vikamade_symfony');
define('PASSWORD', 'udaxqfpu');
define('NAME_BD', 'vikamade_symfony');

//connect to DB
$connect = mysqli_connect(HOST, USER, PASSWORD, NAME_BD);

//search for message in DB for specific login
$sql="SELECT * FROM Messages WHERE login='".$login."' ORDER BY dt DESC ";
$result = mysqli_query($connect, $sql);

//set criteria for check if no messages for this login
if ($result->num_rows>0) {
    $crit='UNSEEN';//select only UNSEEN messages
} else {
    $crit='ALL';//if no messages for this login - select ALL messages
}

// connect to gmail
$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';//to INBOX folder
$username = $login;//yours login


// try to connect 
$inbox = imap_open($hostname,$username,$password) or die('Ошибка подключения к Gmail: ' . imap_last_error());

//if connected - search message for special criteria
$emails = imap_search($inbox,$crit);


// if emails are returned, cycle through each... 
if($emails) {
	
    
    
	// begin output table
	$output = '';
    
    //begin output values
    $val='';
    
    //i - counter of group values
    $i=1;
	
	// put the newest emails on top 
	rsort($emails);
	
	// for every email...
	foreach($emails as $email_number) {   
	   //if email not first - put comma in query
		if ($i>1){
		  $val.=", ";
		}
		// get information specific to this email 
        //get header for email
		$overview = imap_fetch_overview($inbox,$email_number,0);
        //get text part of email
		$msg = base64_decode(imap_fetchbody($inbox,$email_number,1));
        
        
        //decode from part
        if (preg_match("/=\?UTF-8\?B\?/", $overview[0]->from)==1){//if base64 encoded
            $subject=preg_split("#=\?UTF-8\?\S?\?([\w_:=\/]*)\?=\s#",$overview[0]->from,0,PREG_SPLIT_DELIM_CAPTURE);
            $str='';
            foreach($subject as $st) {
                $str .= base64_decode($st);
            }
            $from = $str; 
        } else {           
            $from=$overview[0]->from;
        }
        
        //decode subject part
        if (preg_match("/=\?UTF-8\?B\?/", $overview[0]->subject)==1){//if base64 encoded
        
        $subject=preg_split("#=\?UTF-8\?\S?\?([\w_:=\/]*)\?=#",$overview[0]->subject,0,PREG_SPLIT_DELIM_CAPTURE);
        $str='';
        foreach($subject as $st) {
            $str .= base64_decode($st);
        }
        } else {
          $subject=preg_split("#=\?UTF-8\?\S?\?([\w_:=\/]*)\?=#",$overview[0]->subject,0,PREG_SPLIT_DELIM_CAPTURE);
            
            $str='';
            foreach($subject as $st) {
                $str .= $st;
            }  
            
        }
        $subject = $str;
        
        //form message part
        $message = $msg;
        
        //form output
        $output.=sprintf("<tr".(($i%2==0)?" class='odd'":"").">
        <td class='dt'>%s</td>
        <td class='from'>%s</td>
        <td class='sbj'>%s</td>
        <td class='msg'>%s</td>
        </tr>",date("Y-m-d H:i:s", $overview[0]->udate),$from,$subject,$message); 
         
         //form value for query
        $val.=sprintf("( '%s', '%s', '%s', '%s', %d )",$login, $from, $subject, $message, $overview[0]->udate);     
		$i++;
	}
	
   //store in db some param      
            
        $sql="INSERT INTO Messages (login, frm, subject, message, dt) VALUES ".$val;
        $result1 = mysqli_query($connect, $sql);
    
} 

/* close the connection */
imap_close($inbox);


//print other message from DB, founded in begin of script
 while($row = $result->fetch_assoc()) {
    $output.=sprintf("<tr".(($i%2==0)?" class='odd'":"").">
        <td class='dt'>%s</td>
        <td class='from'>%s</td>
        <td class='sbj'>%s</td>
        <td class='msg'>%s</td>
        </tr>",date("Y-m-d H:i:s", $row["dt"]),$row["frm"],$row["subject"],$row["message"]); 
        $i++;
 }

mysqli_close($connect);

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8" />
	<TITLE>Сканер почты</TITLE>
    <link rel="stylesheet" type="text/css" href="style.css">

</head>

<body>

<h1 id="title" >Ваши сообщения в папке Входящие</h1>

<table id="rezt">
<tr class="odd">
<th>Дата</th>
<th>От кого</th>
<th>Тема</th>
<th class="msg">Сообщение</th>
</tr>
<?php
echo $output;
?>
</table>







</body>
</html>