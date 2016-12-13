<?php
/* Author: Lionel Palazzi * eGenèse France * Licence MIT */ 

if(file_exists('private-config.php'))
	include('private-config.php');											
 else 
	include('dummy-config.php');										
 
/* START RUN ---------------Do not modify below ---------------------- */
$sIp =   trim(exec('curl -sk GET "'.STATIC_URL_GET_IP.'"'));
if(empty($sIp))	
	die;
if(!file_exists(__DIR__.'/myIp')) 
	exec('echo '.$sIp.' > '.__DIR__.'/myIp'); 
$sSavedIp = trim(file_get_contents(__DIR__.'/myIp'));
if($sSavedIp==$sIp)	 // Ip has not changed
	die('/'); 
$oCF = new CloudFlareClient(SUBDOMAIN);
$sDnsIp = $oCF->getRecord('A');
echo "\nMyIP: $sIp / DNS A $sDnsIp\n";
if($sIp!=$sDnsIp) // IP must be updated for DNS A record
	$oCF->updateLastRecordContent($sIp);
/* END RUN ---------------------------------------------------- */

class CloudFlareClient {

	function __construct($sDomain,$bDBug=false) {
		if(empty($sDomain))
			die('subdomain required');
		$this->bDbug = $bDBug;
		$aE = explode('.',$sDomain);
		if(count($aE)<3)
			die('a subdomain is like sub.domain.com');
		if(count($aE)>3)
			die('this program does not work more than one level deep, sub2.sub1.domain.com is not ok
				, sub1.domain.com is ok');
		$this->sSubDomain = $sDomain;
		$this->sZoneDomain = $aE[1].'.'.$aE[2];
	}

	function getRecord($sType) {
		$sCmd = 'curl -sk GET "https://api.cloudflare.com/client/v4/zones?name='.$this->sZoneDomain
			.'" \
			 -H "X-Auth-Email: '.CLOUDFLARE_USER.'" \
			 -H "X-Auth-Key: '.CLOUDFLARE_AUTH.'" \
			 -H "Content-Type: application/json"';
		$oResp = json_decode(exec($sCmd));
		if($oResp->success) {
			$this->sZoneId=$oResp->result[0]->id;
			$this->sRecordType = $sType;
			$sCmd = 'curl -sk GET "https://api.cloudflare.com/client/v4/zones/'.$this->sZoneId
			.'/dns_records?type='.$this->sRecordType.'&name='.$this->sSubDomain.'" \
			 -H "X-Auth-Email: '.CLOUDFLARE_USER.'" \
			 -H "X-Auth-Key: '.CLOUDFLARE_AUTH.'" \
			 -H "Content-Type: application/json"';
			$oResp = json_decode(exec($sCmd));
			if($oResp->success) {
				if(empty($oResp->result[0]))
					die('set up '.SUBDOMAIN.' in your Cloudflare zone '
					.$this->sZoneDomain); // <- could be created instead..
				$this->sRecordId = $oResp->result[0]->id;
				$this->sRecordContent = $oResp->result[0]->content;
				$this->sRecordTTL = $oResp->result[0]->ttl;
				if(!empty($oResp->result[0]->proxied))
					$this->sRecordProxied = $oResp->result[0]->proxied;
				return $this->sRecordContent;
			} else 
				if($this->bDbug)
					print_r($oResp->errors);
		} else 
			if($this->bDbug)
				print_r($oResp->errors);
	}  

	function updateLastRecordContent($sContent){
		$sCmd = 'curl -X PUT "https://api.cloudflare.com/client/v4/zones/'.$this->sZoneId
			.'/dns_records/'.$this->sRecordId.'" \
			 -H "X-Auth-Email: '.CLOUDFLARE_USER.'" \
			 -H "X-Auth-Key: '.CLOUDFLARE_AUTH.'" \
			 -H "Content-Type: application/json" \
			--data \'{"type":"'.$this->sRecordType.'","name":"'.$this->sSubDomain
			.'","content":"'.$sContent.'","ttl":'.$this->sRecordTTL.',"proxied":'
			.(empty($this->sRecordProxied)?'false':'true').'}\'';
		$oResp = json_decode(exec($sCmd));
		if($oResp->success) {
			exec('echo '.$oResp->result->content.' > '.__DIR__.'/myIp'); 
			die(date('Ymd H:i:is').' DNS updated with '.$oResp->result->name
			.' '.$oResp->result->type.' '.$oResp->result->content);
		} else 
			die(date('Ymd H:i:is').' failed to update DNS');
	}
}
?>
