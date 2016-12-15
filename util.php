<?php
Class Util {
	protected $id;
	protected $name;
	protected $cookies;
	protected $examArrangement;
	protected $schedule;
	protected $grades;

	public function __construct($cookies=''){
		if($cookies !== ''){
			$ret = self::fetch('http://jwgl.hnuc.edu.cn/jwxs/Xsxk/KwGl_Kwxx_xslist.asp', [
				'Cookie: ' . $cookies
			]);
			if(strstr($ret['res_body'], '考试安排', 'UTF-8')){
				$this->cookies = $cookies;
				preg_match('/\[学号：(\d+)/', $ret['res_body'], $pregRes);
				$this->id = $pregRes[1];
				preg_match('/\[姓名：(.+?)\]/', $ret['res_body'], $pregRes);
				$this->name = $pregRes[1];
				$this->examArrangement = [];
				preg_match('/\[考试学期：(.+?)\]/', $ret['res_body'], $pregRes);
				$this->examArrangement['term'] = $pregRes[1];
				preg_match_all('/<(td|nobr).*?>(.+?)<\/\1>/', $ret['res_body'], $pregRes);
				$tmpTable = [[]];
				$i = 0; $j = 0;
				foreach($pregRes[2] as $v){
					$tmpTable[$j][] = preg_replace('/<.+?>/', '', $v);
					if((++$i)%8 === 0){
						$j++;
					}
				}
				$this->examArrangement['table'] = $tmpTable;
			}else{
				throw new Exception("Invalid Cookies", -1);
			}
		}
	}

	public function login($id, $password, $type = 'xs'){
		$this->id = $id;
		$res = self::fetch('http://jwgl.hnuc.edu.cn/JwXs/LoginCheck.asp', [
			'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36'
		], 'POST', [
			'logindate' => '',
			'LoginLb'=> $type,
			'Account'=> $id,
			'PassWord'=> $password,
			'x'=> '27',
			'y'=> '11',
			'AccessID'=> date('YmdHis').'.7674372',
			'DiskNumber'=> ''
		]);
		$this->cookies = $res['res_set_cookie'];
		if(preg_match('/alert\("(.+错误.+?)"\);/', $res['res_body'], $pregRes)){
			throw new Exception("登录失败：" . str_replace('\n', "\n", $pregRes[1]), -3);
		}
		return $this->cookies;
	}

	// 获得cookies
	public function getCookies(){
		return $this->cookies;
	}

	// 获得考试安排
	public function getExamArrangement(){
		if(is_null($this->examArrangement)){
			$this->__construct($this->cookies);
		}
		return $this->examArrangement;
	}

	// 获得课程表
	public function getSchedule(){
		if(is_null($this->schedule)){

			$res = self::fetch('http://jwgl.hnuc.edu.cn/jwxs/Xsxk/Xk_XsxkRight_Kb.asp', [
					'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
					'Accept-Language:zh-CN,zh;q=0.8',
					'Cookie:' . $this->cookies,
					'Referer:http://jwgl.hnuc.edu.cn/jwxs/Xsxk/Xk_XsxkRight_Kb_top.asp?XH=' . $this->id,
					'Upgrade-Insecure-Requests:1',
					'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36',
				], 'POST', [
					'Xnxqh' => '2016-2017-1',
					'Xh' => $this->id,
					'noshowtool' => 'true',
					'showKbmx' => 'ON'
				]);
			preg_match_all('/<td width="130".+?>([\s\S]+?)<\/td>/', $res['res_body'], $pregRes);
			$i = 0; $j = 0;
			$tmpTable = [[]];
			foreach($pregRes[1] as $v){
				$tmpTable[$j][] = trim(preg_replace(['/<br>/', '/<.+?>|&.+?;/'], ["\n", ''], $v));
				if((++$i)%7 === 0){
					$j++;
				}
			}
			$this->schedule = $tmpTable;
		}
		return $this->schedule;
	}

	// 获得成绩
	public function getGrades($term){
		if(is_null($this->grades[$term])){
			$res = self::fetch('http://jwgl.hnuc.edu.cn/jwxs/Xsxk/Xk_CjZblist.asp?flag=find', [
				'Cookie: '. $this->cookies
				], 'POST',[
				'XH' => $this->id,
				'noshowtool' => '',
				// 'Xnxqh' => '2015-2016-2',
				'Xnxqh' => $term,
				'KhfsCode' => '',
				'D1' => '',
				'KcFlCode' => '',
				'Kcbm' => '',
				'TblName' => 'V_Cj_XsCjZb',
				// 'cmdok' => iconv('UTF-8', 'GBK', '查  询'),
				]);
			preg_match_all('/<td (?:wi|he).+?>(.+?)<\/td>/', $res['res_body'], $pregRes);
			unset($pregRes[1][0]);
			$i = 0; $j = 0;
			$tmpTable = [[]];
			foreach($pregRes[1] as $v){
				$tmpTable[$j][] = preg_replace('/<.+?>|&.+?;/', '', $v);
				if((++$i) %13 === 0){
					$j++;
				}
			}
			$this->grades[$term] = $tmpTable;
		}
		return $this->grades[$term];
	}

	static function fetch($url, $header = [], $method = 'GET', $data = []){
		$ch = curl_init();
		$opt = [
			CURLOPT_URL => $url,
			CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		];
		if(strtoupper($method) === 'POST'){
			$tempArr = [];
			foreach($data as $key => $v){
				$tempArr[] =  urlencode($key) . '=' . urlencode($v);
			}
			$opt[CURLOPT_POSTFIELDS] = implode($tempArr, '&');
		}
		curl_setopt_array($ch, $opt);
		$tempRes = curl_exec($ch);
		if(empty($tempRes)) throw new Exception("网络连接错误", -2);
		$tempInfo = curl_getinfo($ch);
		curl_close($ch);
		$ret = [];
		$tempResHeader = substr($tempRes, 0, $tempInfo['header_size']);
		$tempPregRes = preg_split('/\r?\n/', trim($tempResHeader));
		$tempCookies = [];
		foreach($tempPregRes as $v){
			if(preg_match('/^Set-Cookie:\s?(.+?);/i', $v, $tempPregRes2)){
				$tempCookies[] = $tempPregRes2[1];
			}
		}
		$ret['res_header'] = $tempPregRes;
		$ret['res_set_cookie'] = implode($tempCookies, '; ');
		$ret['res_body'] = iconv('GBK', 'UTF-8', substr($tempRes, $tempInfo['header_size']));
		return $ret;
	}
}
