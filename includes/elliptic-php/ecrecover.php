<?php

require_once("vendor/autoload.php");
require_once("kornrunner/Keccak.php");

use Elliptic\EC;
use kornrunner\Keccak;

function pubKeyToAddress($pubkey) {
	return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
}

function ecrecover($message, $signature) {
	$msglen = strlen($message);
	//echo "msglen:$msglen\n";
	$hash   = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
	//echo "hash:$hash\n";
	$sign   = ["r" => substr($signature, 2, 64), 
			   "s" => substr($signature, 66, 64)];
	$recid  = ord(hex2bin(substr($signature, 130, 2))) - 27; 
	//echo "$recid\n";
	if ($recid != ($recid & 1)) 
		return "";
	
	$ec = new EC('secp256k1');
	//echo "secp256k1\n";
	//var_dump($ec);
	$pubkey = $ec->recoverPubKey($hash, $sign, $recid);
	//var_dump($pubkey);
	return pubKeyToAddress($pubkey)."\n";
	//return $address == pubKeyToAddress($pubkey);
}

if(0){
	$addr= ecrecover($message, $signature);
	$address   = "0x5a214a45585b336a776b62a3a61dbafd39f9fa2a";
	$message   = "I like signatures";
	// signature returned by eth.sign(address, message)
	$signature = "0xacb175089543ac060ed48c3e25ada5ffeed6f008da9eaca3806e4acb707b9481401409ae1f5f9f290f54f29684e7bac1d79b2964e0edcb7f083bacd5fc48882e1b";
	echo ecrecover($message, $signature);

}