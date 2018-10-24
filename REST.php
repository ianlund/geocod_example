<?

	class REST
	{
		private $authUser;
		private $authPwd;
		private $url;
		private $data;
		private $headers;
			
		static function factory()
		{
			return new self();
		}
			
		public function auth($user, $pwd)
		{
			$this->authUser = $user;
			$this->authPwd = $pwd;
			return $this;
		}
			
		public function url($url)
		{
			$this->url = $url;
			return $this;
		}
			
		public function data($data)
		{
			$this->data = $data;
			return $this;
		}
			
		public function headers($headers)
		{
			$this->header = $headers;
			return $this;
		}
			
		static function uri($url, $data)
		{
			return $url.($data ? '?'.http_build_query($data) : null);
		}
			
		public function get()
		{
			$uri = self::uri($this->url, $this->data);
				
			$opts = [
				CURLOPT_URL => $uri,
				CURLOPT_HEADER => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLINFO_HEADER_OUT => true
			];
				
			if($this->headers):
				$opts['CURLOPT_HTTPHEADER'] = $this->headers;
			endif;
				
			if($this->authUser || $this->authPwd):
				$opts[CURLOPT_USERPWD] = $this->authUser.':'.$this->authPwd;
			endif;
				
			$c = curl_init();
			curl_setopt_array($c, $opts);
			$response = curl_exec($c);
			if($response === FALSE):
				$error = curl_error($c);
				curl_close($c);
				throw new Exception($error);
			endif;
			$info = curl_getinfo($c);
			curl_close($c);
				
			$header = trim(substr($response, 0, $info['header_size']));
			$body = substr($response, $info['header_size']);
				
			return (object)['info' => $info, 'header' => $header, 'body' => $body];
		}
	}
		
?>
