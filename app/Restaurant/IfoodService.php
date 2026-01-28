<?php

namespace App\Restaurant;

class IfoodService{

	protected $config = null;
	public function __construct($config){
		$this->config = $config;
	}

	public function getUserCode(){
		$url = "https://merchant-api.ifood.com.br/authentication/v1.0/oauth/userCode";

		$curl = curl_init();

		$headers = [];
		curl_setopt($curl, CURLOPT_URL, $url . "?clientId=".$this->config->clientId);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($curl, CURLOPT_HEADER, false);
		$result = json_decode(curl_exec($curl));
		curl_close($curl);

		if(isset($result->authorizationCodeVerifier)){
			$authorizationCodeVerifier = $result->authorizationCodeVerifier;
			$verificationUrlComplete = $result->verificationUrlComplete;
			$userCode = $result->userCode;

			if($userCode){
				$item = $this->config;
				$item->userCode = $userCode;
				$item->authorizationCodeVerifier = $authorizationCodeVerifier;
				$item->verificationUrlComplete = $verificationUrlComplete;
				$item->save();
				return $userCode;
			}
			return "";
		}else{
			echo "Algo errado, retorno iFood: ";
			print_r($result);
			die;
		}
	}

	public function oAuthToken(){
		$url = "https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token";

		$ch = curl_init();
		$grantType = $this->config->grantType;

		if($this->config->accessToken != ""){
			$grantType = 'refresh_token';
		}

		$clientId = $this->config->clientId;
		$clientSecret = $this->config->clientSecret;
		$authorizationCode = $this->config->authorizationCode;
		$authorizationCodeVerifier = $this->config->authorizationCodeVerifier;

		$params = "?grantType=$grantType&clientId=$clientId&clientSecret=$clientSecret&authorizationCode=$authorizationCode&authorizationCodeVerifier=$authorizationCodeVerifier";

		if($this->config->accessToken != ""){
			$params .= "&refreshToken=" . $this->config->refreshToken;
		}
		$headers = [];
		curl_setopt($ch, CURLOPT_URL, $url . $params);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		if(!isset($result->error)){

			$accessToken = $result->accessToken;
			$refreshToken = $result->refreshToken;
			$item = $this->config;
			$item->accessToken = $result->accessToken;
			$item->refreshToken = $result->refreshToken;

			$item->save();
			return ['success' => 1, 'token' => $accessToken];
		}else{
			$item = $this->config;
			$item->save();
			return ['success' => 0, 'message' => $result->error->message];

		}

	}

	public function newToken(){
		$url = "https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token";

		$ch = curl_init();
		$grantType = $this->config->grantType;

		$clientId = $this->config->clientId;
		$clientSecret = $this->config->clientSecret;
		$authorizationCode = $this->config->authorizationCode;
		$authorizationCodeVerifier = $this->config->authorizationCodeVerifier;

		$params = "?grantType=$grantType&clientId=$clientId&clientSecret=$clientSecret&authorizationCode=$authorizationCode&authorizationCodeVerifier=$authorizationCodeVerifier";

		$headers = [];
		curl_setopt($ch, CURLOPT_URL, $url . $params);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		if(!isset($result->error)){

			$accessToken = $result->accessToken;
			$refreshToken = $result->refreshToken;
			$item = $this->config;
			$item->accessToken = $result->accessToken;
			$item->refreshToken = $result->refreshToken;

			$item->save();
			return ['success' => 1, 'token' => $accessToken];
		}else{
			// echo $result->error->message;
			// die;
			$item = $this->config;
			$item->save();
			return ['success' => 0, 'message' => $result->error->message];

		}

	}

	public function getCatalogs(){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/catalogs";
		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		return $result;

	}

	public function getCategories(){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/catalogs/".
		$this->config->catalogId."/categories";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		return $result;

	}

	public function getProducts($page = 1){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/products?limit=20&page=$page";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		return $result;

	}

	public function findProduct($id){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/product/$id";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		return $result;

	}

	public function storeProduct($data){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/products";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			'Content-Type: application/json'
		];
		$payload = json_encode($data);
		// print_r($payload);
		// die;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function updateProduct($data, $id){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/products/$id";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			'Content-Type: application/json'
		];
		$payload = json_encode($data);
		// print_r($payload);
		// die;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function addStockProduct($data){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/inventory";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			'Content-Type: application/json'
		];
		$payload = json_encode($data);
		// print_r($payload);
		// die;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function getStock($id){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/inventory/$id";
		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);
		return $result;

	}

	public function associationProductCategory($categoryId, $productId, $data){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/categories/$categoryId/products/$productId";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			'Content-Type: application/json'
		];
		$payload = json_encode($data);

		// print_r($payload);
		// die;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function deleteProduct($categoryId, $productId){
		$url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/categories/$categoryId/products/$productId";

		// $url = "https://merchant-api.ifood.com.br/catalog/v1.0/merchants/".$this->config->merchantId."/products/$productId";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	// PLC%2CREC%2CCFM
	public function getOrders($types){
		$url = "https://merchant-api.ifood.com.br/order/v1.0/events:polling?types=$types&groups=ORDER_STATUS%2CDELIVERY";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			"x-polling-merchants: " . $this->config->merchantId
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function getOrderDetail($id){
		$url = "https://merchant-api.ifood.com.br/order/v1.0/orders/$id";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function orderConfirm($id){
		$url = "https://merchant-api.ifood.com.br/order/v1.0/orders/$id/confirm";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function cancellation($id, $motivo, $codigo){
		$url = "https://merchant-api.ifood.com.br/order/v1.0/orders/$id/requestCancellation";
		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			'Content-Type: application/json'
		];

		$data = [
			// 'reason' => $motivo != "" && $motivo != null ? $motivo : "Cancelamento por motivo desconhecido",
			'reason' => $motivo,
			'cancellationCode' => $codigo
		];
		$payload = json_encode($data);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}


	public function orderDispatch($id){
		$url = "https://merchant-api.ifood.com.br/order/v1.0/orders/$id/dispatch";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function requestDriver($id){
		$url = "https://merchant-api.ifood.com.br/order/v1.0/orders/$id/requestDriver";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function statusMerchant(){
		$url = "https://merchant-api.ifood.com.br/merchant/v1.0/merchants/".$this->config->merchantId."/status";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;
	}

	public function setInterruption($data){
		$url = "https://merchant-api.ifood.com.br/merchant/v1.0/merchants/".$this->config->merchantId."/interruptions";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
			'Content-Type: application/json'
		];

		$payload = json_encode($data);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function deleteInterruption($id){
		$url = "https://merchant-api.ifood.com.br/merchant/v1.0/merchants/".$this->config->merchantId."/interruptions/$id";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

	public function getInterruptions(){
		$url = "https://merchant-api.ifood.com.br/merchant/v1.0/merchants/".$this->config->merchantId."/interruptions";

		$ch = curl_init();
		$headers = [
			"Authorization: Bearer " . $this->config->accessToken,
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		$result = json_decode(curl_exec($ch));
		curl_close($ch);

		return $result;

	}

}