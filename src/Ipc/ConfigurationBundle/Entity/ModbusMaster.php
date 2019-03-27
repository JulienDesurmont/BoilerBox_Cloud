<?php
namespace Ipc\ConfigurationBundle\Entity;

/**
 * Tiré de Phpmodbus Copyright (c) 2004, 2012 Jan Krakora
 *  
 * This source file is subject to the "PhpModbus license" that is bundled
 * with this package in the file license.txt.
 *   
 *
 * @copyright  Copyright (c) 2004, 2012 Jan Krakora
 * @license PhpModbus license 
 * @category Phpmodbus
 * @tutorial Phpmodbus.pkg 
 * @package Phpmodbus 
 * @version $id$
 *  
 */
/**
 * ModbusMaster
 *
 * This class deals with the MODBUS master
 *  
 * Implemented MODBUS master functions:
 *   - FC  1: read coils
 *   - FC  2: read input discretes
 *   - FC  3: read multiple registers
 *   - FC  6: write single register
 *   - FC 15: write multiple coils
 *   - FC 16: write multiple registers
 *   - FC 23: read write registers
 *   
 * @author Jan Krakora
 * @copyright  Copyright (c) 2004, 2012 Jan Krakora
 * @package Phpmodbus  
 *

CODE FONCTION
01 : Lecture de N bits de sortie
02 : Lecture de N bits d’entrée
03 : Lecture de N mots de sortie
04 : Lecture de N mots d’entrée
05 : Ecriture d’un bit de sortie
06 : Ecriture d’un mot de sortie
07 : Lecture d’un status d’exception
08 : Diagnostic
09 : non utilisé
10 : non utilisé
11 : Lecture du compteur d’évènements
12 : Lecture évènements connexion
13 : non utilisé
14 : non utilisé
15 : Ecriture de N bits de sortie
16 : Ecriture de N mots de sortie
17 : Identification esclave

*/

class ModbusMaster {
  private $sock;
  private $errorMsg		= '';
  public $host 			= null;
  public $port 			= "502";  //	Port de communication ModBus
  public $client 		= "";
  public $client_port 		= "502";
  public $status;
  public $timeout_sec 		= 5; // Timeout 5 sec
  public $endianness 		= 0; // Endianness codding (little endian == 0, big endian == 1) 
  public $socket_protocol 	= "TCP"; // Socket protocol (TCP, UDP)


  /* Constructeur permettant de définir l'adresse Ip */
    function __construct($adresseIp)
    {
        $this->host 		= $adresseIp;
    }
  
  /**
   * ModbusMaster Constructor
   *
   * @param String $host 	: Adresse Ip de l'automate E.g. "192.168.1.1"
   * @param String $protocol 	: Protocol du Socket (TCP, UDP)   
   */         
  function ModbusMaster($host, $protocol){
    $this->socket_protocol 	= $protocol;
    $this->host 		= $host;
  }


  /**
   * __toString
   *
   * Magic method
   */
  function  __toString() {
      return "<pre>" . $this->status . "</pre>";
  }

   public function getErrorMsg()
   {
	return($this->errorMsg);
   }

  /**
   * Etablissement de la connexion TCP par socket
   *
   * @return bool
   */
  private function connect(){
    // 1 	Création du socket
    if ($this->socket_protocol == "TCP"){ 
	//      Création d'un socket de type : IPv4 - 
        $this->sock 	= socket_create(AF_INET, SOCK_STREAM, SOL_TCP);      
	if($this->sock == false)
	{
	    $this->errorMsg     = "Echec de la création de la socket".socket_strerror(socket_last_error($this->sock));
	    return false;
	}
    } else {
        $this->errorMsg = "Protocol TCP non défini";
	return false;
	//throw new \Exception("Protocol TCP non défini");
    }
    //	2 	Liaison de l'adresse Ip du client et du socket de communication
    if (strlen($this->client)>0){
        $result 	= socket_bind($this->sock, $this->client, $this->client_port);
        if ($result === false) {
	    $this->errorMsg 	= "Echec de liaison de socket ($this->client:$this->client_port) : ".socket_strerror(socket_last_error($this->sock));
	    return false;
            //throw new \Exception("Echec de liaison de socket.</br> : ($result)".socket_strerror(socket_last_error($this->sock)));
        } else {
            $this->status 	.= "Lié\n";
        }
    }

    // 3 	Définition du délai d'exécution pour les fonctions sortantes bloquantes à 1 seconde 0 milliseconde
    if(!socket_set_option($this->sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)))
    {
	$this->errorMsg .=' - Impossible de définir l\'option SO_SNDTIMEO du socket : '. socket_strerror(socket_last_error());
    }

    // 4 	Création de la connexion sur le socket
    $result = @socket_connect($this->sock, $this->host, $this->port);
    if ($result === false) {
	$this->errorMsg .= " - Echec de connexion socket ($this->host:$this->port) : ".socket_strerror(socket_last_error($this->sock));
        //throw new \Exception("Echec de connexion socket</br> : ($result)".socket_strerror(socket_last_error($this->sock)));
	return false;
    } else {
        $this->status .= "Connected\n";
        return true;        
    }    
  }

  /**
   * disconnect
   *
   * Deconnexion de socket
   */
  private function disconnect(){    
    socket_close($this->sock);
    $this->status .= "Disconnected\n";
  }

  /**
   * Envoi de paquet via Modbus : Ecriture dans le socket
   *
   * @param string $packet
   */
  private function send($packet){
    //	Ecriture du buffer '$packet' dans le socket
    socket_write($this->sock, $packet, strlen($packet));  
    $this->status .= "Send\n";
  }

  /**
   * rec
   *
   * Receive data from the socket
   *
   * @return bool
   */
  private function rec(){
    //	Mise du socket en mode non bloquant ( option 0_NONBLOCK )
    socket_set_nonblock($this->sock);
    $readsocks[] 	= $this->sock;     
    $writesocks 	= NULL;
    $exceptsocks 	= NULL;
    $rec 		= "";
    $lastAccess 	= time();
    //	Exécute l'appel système select() sur un tableau de sockets avec une durée d'expiration
    while (socket_select($readsocks,$writesocks,$exceptsocks,0,300000) !== FALSE)
    {
        $this->status .= "Wait data ... \n";
        if(in_array($this->sock, $readsocks)) {
	    //	Reçoit des données (jusqu'à 2000 octets) d'un socket connecté et les transmets à $rec
            while(@socket_recv($this->sock, $rec, 2000, 0)) {
                $this->status .= "Data received\n";
                return $rec;
            }
            $lastAccess = time();
        }else{             
            if(time()-$lastAccess >= $this->timeout_sec) {
		$this->errorMsg = "Watchdog time expired [ ".$this->timeout_sec." sec]!!! Connection à l'automate ".$this->host." non établie.";
		return false;
            }
        }
	//	Réinitialisation du tableau des sockets
        $readsocks[] = $this->sock;
    }
  } 
  
  /**
   * responseCode
   *
   * Check the Modbus response code
   *
   * @param string $packet
   * @return bool
   */
  private function responseCode($packet){    
    if((ord($packet[7]) & 0x80) > 0) {
      // failure code
      $failure_code = ord($packet[8]);
      // failure code strings
      $failures = array(
        0x01 => "ILLEGAL FUNCTION",
        0x02 => "ILLEGAL DATA ADDRESS",
        0x03 => "ILLEGAL DATA VALUE",
        0x04 => "SLAVE DEVICE FAILURE",
        0x05 => "ACKNOWLEDGE",
        0x06 => "SLAVE DEVICE BUSY",
        0x08 => "MEMORY PARITY ERROR",
        0x0A => "GATEWAY PATH UNAVAILABLE",
        0x0B => "GATEWAY TARGET DEVICE FAILED TO RESPOND");
      // get failure string
      if(key_exists($failure_code, $failures)) {
        $failure_str = $failures[$failure_code];
      } else {
        $failure_str = "UNDEFINED FAILURE CODE";
      }
      // exception response
      $this->errorMsg = "Modbus response error code: $failure_code ($failure_str)";
      return false;
      //throw new \Exception("Modbus response error code: $failure_code ($failure_str)");
    } else {
      $this->status .= "Modbus response error code: NOERROR\n";
      return true;
    }    
  }
  
  
  /**
   * readMultipleRegisters
   *
   * Modbus function FC 3(0x03) - Read Multiple Registers.
   * 
   * This function reads {@link $quantity} of Words (2 bytes) from reference 
   * {@link $referenceRead} of a memory of a Modbus device given by 
   * {@link $unitId}.
   *    
   *
   * @param int $unitId usually ID of Modbus device 
   * @param int $reference Reference in the device memory to read data (e.g. in device WAGO 750-841, memory MW0 starts at address 12288).
   * @param int $quantity Amounth of the data to be read from device.
   * @return false|Array Success flag or array of received data.
   */
  function readMultipleRegisters($unitId, $reference, $quantity){
    $this->status 	.= "readMultipleRegisters: START\n";

    // Etablissement de la connexion par socket
    if($connexion = $this->connect() == true)
    {
    	// send FC 3    
    	//	Création d'un paquet de données tcp modbus
    	$packet 		= $this->readMultipleRegistersPacketBuilder($unitId, $reference, $quantity);
	
    	//	Log du message en hexadécimal à envoyer 
    	$this->status 		.= $this->printPacket($packet);    

	//echo "Trame ADU lecture modbus : ".$this->status."<br />";

    	//	Ecriture des données du packet dans le socket = Envoi du message
    	$this->send($packet);

    	//	Réception de la réponse
    	$rpacket 		= $this->rec();
    	if($rpacket != false)
    	{
    	    //	Log de la réponse en hexadécimal
    	    $this->status 	.= $this->printPacket($rpacket);    
	    //echo "Reponse : ".$this->status."<br />";

    	    // Création d'un tableau contenant les caractères ASCII de la réponse
    	    $receivedData 	= $this->readMultipleRegistersParser($rpacket);

    	    // disconnect
    	    $this->disconnect();
    	    $this->status 	.= "readMultipleRegisters: DONE\n";

    	    // Retour du tableau des caractères ASCII représentant la réponse
    	    return $receivedData;
    	}else{
	    return 'false0';
    	}
    }else{
	return 'false1';
    }
  }
  
  /**
   * fc3
   *
   * Alias to {@link readMultipleRegisters} method.
   *
   * @param int $unitId
   * @param int $reference
   * @param int $quantity
   * @return false|Array
   */
  function fc3($unitId, $reference, $quantity){
    return $this->readMultipleRegisters($unitId, $reference, $quantity);
  }  
  


  /**
   * readMultipleRegistersPacketBuilder
   *
   * Packet FC 3 builder - read multiple registers
   *
   * @param int $unitId
   * @param int $reference
   * @param int $quantity
   * @return string
   */
  private function readMultipleRegistersPacketBuilder($unitId, $reference, $quantity){
    $dataLen = 0;
    //	Création de la donnée 

    // build data section
    $buffer1 = "";					// Numéro d'esclave				(1 Byte)

    // Création de la Trame de Lecture
    $buffer2 = "";
    $buffer2 .= IecType::iecBYTE(3);             	// Code Fonction 'FC 3' = 3(0x03) = Lecture	(1 Byte)
    // build body - read section    
    $buffer2 .= IecType::iecINT($reference);  		// Adresse du 1er mot (= 2005)			(2 Bytes) 
    $buffer2 .= IecType::iecINT($quantity);       	// Nombre de mots à lire			(2 Bytes)

    $dataLen += 5;

    // Création de l'entête de Trame Modbus : Modbus Application Protocol (MBAP) Header (7 Bytes) build header
    $buffer3 = '';
    $buffer3 .= IecType::iecINT(rand(0,65000));   	// transaction ID				(2 Bytes)
    $buffer3 .= IecType::iecINT(0);               	// protocol ID = 0 Pour le protocal ModBus	(2 Bytes)
    $buffer3 .= IecType::iecINT($dataLen + 1);    	// Length including the UnitID and Data Fields	(2 Bytes)
    $buffer3 .= IecType::iecBYTE($unitId);        	// Unit ID 					(1 Byte)

    // return packet string
    return $buffer3.$buffer2.$buffer1;
  }
  
  /**
   * readMultipleRegistersParser
   *
   * FC 3 response parser
   *
   * @param string $packet
   * @return array
   */
  private function readMultipleRegistersParser($packet){    
    $data = array();
    // check Response code
    if($this->responseCode($packet) != false)
    {
    	// get data	(ord retourne le code ASCII d'un caractère)
	// $packet[8] -> Nombre d'octet à lire
    	for($i=0;$i<ord($packet[8]);$i++){
    	  $data[$i] = ord($packet[9+$i]);
    	}    
    }
    return $data;
  }
  
  /**
   * writeSingleRegister
   *
   * Modbus function FC6(0x06) - Write Single Register.
   *
   * This function writes {@link $data} single word value at {@link $reference} position of 
   * memory of a Modbus device given by {@link $unitId}.
   *
   *
   * @param int $unitId usually ID of Modbus device 
   * @param int $reference Reference in the device memory (e.g. in device WAGO 750-841, memory MW0 starts at address 12288)
   * @param array $data Array of values to be written.
   * @param array $dataTypes Array of types of values to be written. The array should consists of string "INT", "DINT" and "REAL".    
   * @return bool Success flag
   */       
  function writeSingleRegister($unitId, $reference, $data, $dataTypes){
    $this->status .= "writeSingleRegister: START\n";
    // connect
    if($connexion = $this->connect() == true)
    {
    // send FC6    
    $packet = $this->writeSingleRegisterPacketBuilder($unitId, $reference, $data, $dataTypes);
    $this->status .= $this->printPacket($packet);    
    $this->send($packet);
    // receive response
    $rpacket = $this->rec();
    if($rpacket != false)
    {
    	$this->status .= $this->printPacket($rpacket);    
    	// parse packet
    	$this->writeSingleRegisterParser($rpacket);
    	// disconnect
    	$this->disconnect();
    	$this->status .= "writeSingleRegister: DONE\n";
    	return true;
    }else{
	return false;
    }
    }else{
	return false;
    }
  }


  /**
   * fc6
   *
   * Alias to {@link writeSingleRegister} method
   *
   * @param int $unitId
   * @param int $reference
   * @param array $data
   * @param array $dataTypes
   * @return bool
   */
  function fc6($unitId, $reference, $data, $dataTypes){    
    return $this->writeSingleRegister($unitId, $reference, $data, $dataTypes);
  }


  /**
   * writeSingleRegisterPacketBuilder
   *
   * Packet builder FC6 - WRITE single register
   *
   * @param int $unitId
   * @param int $reference
   * @param array $data
   * @param array $dataTypes
   * @return string
   */
  private function writeSingleRegisterPacketBuilder($unitId, $reference, $data, $dataTypes){
    $dataLen = 0;
    // build data section
    $buffer1 = "";
    foreach($data as $key=>$dataitem) {
      $buffer1 .= IecType::iecINT($dataitem);   // register values x
      $dataLen += 2;
      break;
    }
    // build body
    $buffer2 = "";
    $buffer2 .= IecType::iecBYTE(6);             // FC6 = 6(0x06)
    $buffer2 .= IecType::iecINT($reference);      // refnumber = 12288
    $dataLen += 3;
    // build header
    $buffer3 = '';
    $buffer3 .= IecType::iecINT(rand(0,65000));   // transaction ID    
    $buffer3 .= IecType::iecINT(0);               // protocol ID    
    $buffer3 .= IecType::iecINT($dataLen + 1);    // lenght    
    $buffer3 .= IecType::iecBYTE($unitId);        //unit ID    
    
    // return packet string
    return $buffer3. $buffer2. $buffer1;
  }
  
  /**
   * writeSingleRegisterParser
   *
   * FC6 response parser
   *
   * @param string $packet
   * @return bool
   */
  private function writeSingleRegisterParser($packet){
    return $this->responseCode($packet);
  }

  
  /**
   * writeMultipleRegister
   *
   * Modbus function FC16(0x10) - Write Multiple Register.
   *
   * This function writes {@link $data} array at {@link $reference} position of 
   * memory of a Modbus device given by {@link $unitId}.
   *
   *
   * @param int $unitId usually ID of Modbus device 
   * @param int $reference Reference in the device memory (e.g. in device WAGO 750-841, memory MW0 starts at address 12288)
   * @param array $data Array of values to be written.
   * @param array $dataTypes Array of types of values to be written. The array should consists of string "INT", "DINT" and "REAL".    
   * @return bool Success flag
   */       
  function writeMultipleRegister($unitId, $reference, $data, $dataTypes){
    $this->status .= "writeMultipleRegister: START\n";
    // connect
    if($connexion = $this->connect() == true)
    {
    // send FC16    
    $packet = $this->writeMultipleRegisterPacketBuilder($unitId, $reference, $data, $dataTypes);
    $this->status .= $this->printPacket($packet);    
    $this->send($packet);
    // receive response
    $rpacket = $this->rec();
    if($rpacket != false)
    {
    	$this->status .= $this->printPacket($rpacket);    
    	// parse packet
    	$this->writeMultipleRegisterParser($rpacket);
    	// disconnect
    	$this->disconnect();
    	$this->status .= "writeMultipleRegister: DONE\n";
    	return true;
    }else{
	return false;
    }
    }else{
	return false;
    }
  }


  /**
   * fc16
   *
   * Alias to {@link writeMultipleRegister} method
   *
   * @param int $unitId
   * @param int $reference
   * @param array $data
   * @param array $dataTypes
   * @return bool
   */
  function fc16($unitId, $reference, $data, $dataTypes){    
    return $this->writeMultipleRegister($unitId, $reference, $data, $dataTypes);
  }


  /**
   * writeMultipleRegisterPacketBuilder
   *
   * Packet builder FC16 - WRITE multiple register
   *     e.g.: 4dd90000000d0010300000030603e807d00bb8
   *
   * @param int $unitId
   * @param int $reference
   * @param array $data
   * @param array $dataTypes
   * @return string
   */
  private function writeMultipleRegisterPacketBuilder($unitId, $reference, $data, $dataTypes){
    $dataLen = 0;
    // build data section
    $buffer1 = "";
    foreach($data as $key=>$dataitem) {
      if($dataTypes[$key]=="INT"){
        $buffer1 .= IecType::iecINT($dataitem);   // register values x
        $dataLen += 2;
      }
      elseif($dataTypes[$key]=="DINT"){
        $buffer1 .= IecType::iecDINT($dataitem, $this->endianness);   // register values x
        $dataLen += 4;
      }
      elseif($dataTypes[$key]=="REAL") {
        $buffer1 .= IecType::iecREAL($dataitem, $this->endianness);   // register values x
        $dataLen += 4;
      }       
      else{
        $buffer1 .= IecType::iecINT($dataitem);   // register values x
        $dataLen += 2;
      }
    }
    // build body
    $buffer2 = "";
    $buffer2 .= IecType::iecBYTE(16);             // FC 16 = 16(0x10)
    $buffer2 .= IecType::iecINT($reference);      // refnumber = 12288      
    $buffer2 .= IecType::iecINT($dataLen/2);        // word count      
    $buffer2 .= IecType::iecBYTE($dataLen);     // byte count
    $dataLen += 6;
    // build header
    $buffer3 = '';
    $buffer3 .= IecType::iecINT(rand(0,65000));   // transaction ID    
    $buffer3 .= IecType::iecINT(0);               // protocol ID    
    $buffer3 .= IecType::iecINT($dataLen + 1);    // lenght    
    $buffer3 .= IecType::iecBYTE($unitId);        //unit ID    
    
    // return packet string
    return $buffer3. $buffer2. $buffer1;
  }
  
  /**
   * writeMultipleRegisterParser
   *
   * FC16 response parser
   *
   * @param string $packet
   * @return bool
   */
  private function writeMultipleRegisterParser($packet){
    return $this->responseCode($packet);
  }


  /**
   * byte2hex : Conversion d'un caractère en hexadécimal
   *
   * @param char $value
   * @return string
   */
  private function byte2hex($value){	//	(>>> = right Shift unsigned)	  & = Operator AND
    $h = dechex(($value >> 4) & 0x0F);	//	(x >>> 4) & 0x0F will make sure that returned value must be from 0 to 15
    $l = dechex($value & 0x0F);
    return "$h$l";
  }

  /**
   * Conversion d'une chaine de caractères en hexadécimal
   *
   * @param string $packet
   * @return string
   */
  private function printPacket($packet){
    $str 	= "";   
    $str 	.= "Packet: "; 
    for($i=0;$i<strlen($packet);$i++){
      $str 	.= $this->byte2hex(ord($packet[$i]));
    }
    $str 	.= "\n";
    return $str;
  }
}

