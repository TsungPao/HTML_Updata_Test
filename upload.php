<?php
class Upload{
	private $filepath = './upload'; //上傳目錄
	private $tmpPath; //PHP檔案臨時目錄
	private $blobNum; //第幾個檔案塊
	private $totalBlobNum; //檔案塊總數
	private $fileName; //檔名
	public function __construct($tmpPath,$blobNum,$totalBlobNum,$fileName){
		$this->tmpPath = $tmpPath;
		$this->blobNum = $blobNum;
		$this->totalBlobNum = $totalBlobNum;
		$this->fileName = $fileName;
		$this->moveFile();
		$this->fileMerge();
	}
	//判斷是否是最後一塊，如果是則進行檔案合成並且刪除檔案塊
	private function fileMerge(){
		if($this->blobNum == $this->totalBlobNum){
			$blob = '';
			for($i=1; $i<= $this->totalBlobNum; $i++){
				$blob .= file_get_contents($this->filepath.'/'. $this->fileName.'__'.$i);
			}
			file_put_contents($this->filepath.'/'. $this->fileName,$blob);
			$this->deleteFileBlob();
		}
	}
	//刪除檔案塊
	private function deleteFileBlob(){
		for($i=1; $i<= $this->totalBlobNum; $i++){
			@unlink($this->filepath.'/'. $this->fileName.'__'.$i);
		}
	}
	//移動檔案
	private function moveFile(){
		$this->touchDir();
		$filename = $this->filepath.'/'. $this->fileName.'__'.$this->blobNum;
		move_uploaded_file($this->tmpPath,$filename);
	}
	//API返回資料
	public function apiReturn(){
		if($this->blobNum == $this->totalBlobNum){
			if(file_exists($this->filepath.'/'. $this->fileName)){
				$data['code'] = 2;
				$data['msg'] = 'success';
				$data['file_path'] = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['DOCUMENT_URI']).str_replace('.','',$this->filepath).'/'. $this->fileName;
			}
		}else{
			if(file_exists($this->filepath.'/'. $this->fileName.'__'.$this->blobNum)){
				$data['code'] = 1;
				$data['msg'] = 'waiting for all';
				$data['file_path'] = '';
			}
		}
		header('Content-type: application/json');
		echo json_encode($data);
	}
	//建立上傳資料夾
	private function touchDir(){
		if(!file_exists($this->filepath)){
			return mkdir($this->filepath);
		}
	}
}
//例項化並獲取系統變數傳參
$upload = new Upload($_FILES['file']['tmp_name'],$_POST['blob_num'],$_POST['total_blob_num'],$_POST['file_name']);
//呼叫方法，返回結果
$data = $upload->apiReturn();
header('Content-type: application/json');
//return json_encode($data);