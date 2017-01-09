<
php

//Security Key, phones need to send this to access the phone API
if($_GET['key'] != "gfsjh39esadsaFDsa"){
	die();
}


$fromServer = "zzz.zzz.zzz.zzz";             //Phones look for the IP the request comes from in the HTTP Post header, configure this in your yealink phone as the remote server IP
$phone  = strtolower(trim($_GET['phone']));  //The phone making the request
$action = strtolower(trim($_GET['action'])); //The action they're performing
$bool   = (int) trim($_GET['bool']);         //Action On or off?


//Define the phones on your network, the port you have forwarded to 80 for that phone and the details of which lines are configured on that phone.
//The forwarding functionality is setup to instruct a phone to dial a certain number to forward a call to. This is because Telecube.com.au doesn't support call forwarding to be initiated from the phone's built in call forwarding function and is instead handled through a dialing code from the handset.
//Port : the public facing port the phone's internal web server has been forwarded to on 'ip'
//lines : how you've setup your lines via BLF or other. So the script knows which button to change the LED on.
//Jane and Bob : Update to the names of your phones.
$devices = array("Bob" => array("ip" => "xxx.xxx.xxx.xxx",
								  "port" => 12740,
								  'lines' => array('Jane' => 'LINE1',
												   'forward' => 'LINE5'), 
								  'forward_calls_to' => '0403111222', 
								  'instruct_phone_to_forward' => TRUE),
				 "Jane"  => array("ip" => "yyy.yyy.yyy.yyy",
								  "port" => 12733,
								  'lines' => array('Bob' => 'LINE1',
												   'forward' => 'LINE5'),
								  'forward_calls_to' => '0466555777',
								  'instruct_phone_to_forward' => TRUE)
				);


//Push XML via a POST Request from our server to the phone.
function push2phone($server, $phone, $phonePort, $data) {
	$data = '<?xml version="1.0" encoding="ISO-8859-1"?>'.$data;
	
	$xml = "xml=".$data;
	$post = "POST / HTTP/1.1\r\n";
	$post .= "Host: $phone\r\n";
	$post .= "Referer: $server\r\n";
	$post .= "Connection: Keep-Alive\r\n";
	$post .= "Content-Type: text/xml\r\n";
	$post .= "Content-Length: ".strlen($xml)."\r\n\r\n";
	
	$fp = @fsockopen ( $phone, $phonePort, $errno, $errstr, 5);
	
	if (!$fp) {
		echo "Error connecting to phone $phone on port $phonePort : $errstr ($errno)<br />\n";
	} else {
		fputs($fp, $post.$xml);
		flush();
		fclose($fp);
        echo "Sent to $phone on port $phonePort\n<br/>";
	}
}
##############################

//Actions are different events that phones on your network will be notified about
//These actions can be broadcast to all phones or a subset of your phones.
//There are two variations for the XML that is to be sent, one for the action being enabled (bool = 1) and one for the action being disabled (bool = 0)
//Options for broadcast_to : all, all_but_self, self, none, comma delimited list of phone names
//A timeout of 0 has the phone display the message until another or blank is sent.
$xmlActions = array('dnd' => array('messages' => array(array( 'broadcast_to' => 'all',
															  'xml_on' =>  '<PhoneStatus
																				Beep="yes"
																				Timeout="0"
																				SessionID="1">
																				<Message
																					Type="alert"
																					Size="large"
																					Align="left"
																					Color="yellow"
																					Icon="DND"
																					>[[PHONE_NAME]] is DND
																				</Message>
																			</PhoneStatus>',
															  'xml_off' => '<PhoneStatus
																				Beep="yes"
																				Timeout="0"
																				SessionID="1">
																				<Message/>
																			</PhoneStatus>'
															),
													  array( 'broadcast_to' => 'all_but_self',
															  'xml_on' =>  '<PhoneExecute Beep="no">
																				<ExecuteItem URI="Led:[[REMOTE_LINE]]_RED=slowflash"/>
																			</PhoneExecute>',
															  'xml_off' => '<PhoneExecute Beep="no">
																				<ExecuteItem URI="Led:[[REMOTE_LINE]]_GREEN=on"/>
																			</PhoneExecute>'
															)
													  )
								  ),
					'forward' => array('messages' => array(array( 'broadcast_to' => 'self',
																  'xml_on' =>  '<PhoneStatus
																					Beep="yes"
																					Timeout="0"
																					SessionID="1">
																					<Message
																						Type="alert"
																						Size="large"
																						Align="left"
																						Color="yellow"
																						Icon="Forward"
																						>[[PHONE_NAME]] is forwarded to [[FORWARD_CALLS_TO_NUMBER]]
																					</Message>
																				</PhoneStatus>',
																  'xml_off' => '<PhoneStatus
																					Beep="yes"
																					Timeout="0"
																					SessionID="1">
																					<Message/>
																				</PhoneStatus>'
																),
														  array( 'broadcast_to' => 'self',
																  'xml_on' =>  '<PhoneExecute Beep="no">
																					<ExecuteItem URI="Led:[[FORWARD_LINE]]_RED=on"/>
                                                                                    <ExecuteItem URI="Dial:*21*[[FORWARD_CALLS_TO_NUMBER]]"/>
																				</PhoneExecute>',
																  'xml_off' => '<PhoneExecute Beep="no">
																					<ExecuteItem URI="Led:[[FORWARD_LINE]]_RED=off"/>
																					<ExecuteItem URI="Dial:*21**"/>
																				</PhoneExecute>'
																)
                                                          )
									  ),
				   );

//The phone making the request.
if(isset($phone)) {
	
	//Fetch the XML to be sent out from our array of actions based on the action
	//the pinging phone has just performed.
	$xmlAction = $xmlActions[$action];
	$xmlToSendArray = array();
	
	foreach($xmlAction['messages'] as $messageIndex => $message) {
		$xmlToSend = "";
		
		if($bool == 1) {
			$xmlToSend = $message['xml_on'];
		} else {
			$xmlToSend = $message['xml_off']; 
		}	

		$xmlToSend = str_replace("[[PHONE_NAME]]", ucfirst($phone), $xmlToSend);
		$xmlToSend = str_replace("[[FORWARD_CALLS_TO_NUMBER]]", $devices[$phone]['forward_calls_to'], $xmlToSend);
		$xmlToSend = str_replace("[[FORWARD_LINE]]", $devices[$phone]['lines']['forward'], $xmlToSend);
		
		switch($message['broadcast_to']) {
			case "all" :
			case "all_but_self" :
							foreach($devices as $deviceKey => $device) {
								//Skip self??
								if($message['broadcast_to'] == 'all_but_self' && $deviceKey == $phone) {
									continue;
								}
								
								$xmlToSend = str_replace("[[REMOTE_LINE]]", $device['lines'][$phone], $xmlToSend);
								push2phone($fromServer, $device['ip'], $device['port'], $xmlToSend);
						    }
							
							break;
			case "self" :   
							push2phone($fromServer, $devices[$phone]['ip'], $devices[$phone]['port'], $xmlToSend);
							break;
							
			case "none" :	break;
			default		: //comma separated list of phone names
							$devicesToSendTo = explode(',', $message['broadcast_to']);
							foreach($devices as $deviceKey => $device) {
								//Skip self??
								if(array_key_exists($deviceKey, $devicesToSendTo )) {									
									$xmlToSend = str_replace("[[REMOTE_LINE]]", $device['lines'][$phone], $xmlToSend);
									push2phone($fromServer, $device['ip'], $device['port'], $xmlToSend);
								}
						    }
							break;
					
		}
	}
}


echo "Done!"
?>
