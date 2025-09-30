<?php
if(!class_exists('DBConnection')){
	require_once('../config.php');
	require_once('DBConnection.php');
}
class SystemSettings extends DBConnection{
	public function __construct(){
		parent::__construct();
	}
	function check_connection(){
		return($this->conn);
	}
	function load_system_info(){
		if(!isset($_SESSION['system_info'])){
			$sql = "SELECT * FROM system_info";
			$qry = $this->conn->query($sql);
				while($row = $qry->fetch_assoc()){
					$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
				}
		}
	}
	function update_system_info(){
		$sql = "SELECT * FROM system_info";
		$qry = $this->conn->query($sql);
			while($row = $qry->fetch_assoc()){
				if(isset($_SESSION['system_info'][$row['meta_field']]))unset($_SESSION['system_info'][$row['meta_field']]);
				$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
			}
		return true;
	}
	public function update_settings_info(): string {
    header('Content-Type: application/json');

    try {
        // 1) Simpan field text
        foreach ($_POST as $key => $value) {
            if ($key === 'welcome_message') $value = addslashes($value);
            if (!in_array($key, ['about','privacy'], true)) {
                $k = $this->conn->real_escape_string($key);
                $v = $this->conn->real_escape_string($value);
                $exists = $this->conn->query("SELECT 1 FROM system_info WHERE meta_field='{$k}'")->num_rows > 0;
                if ($exists) {
                    $this->conn->query("UPDATE system_info SET meta_value='{$v}' WHERE meta_field='{$k}'");
                } else {
                    $this->conn->query("INSERT INTO system_info (meta_field, meta_value) VALUES ('{$k}','{$v}')");
                }
            }
        }

        // 2) Direktori upload
        $rootDir  = dirname(__DIR__);            // .../admin/classes
        $baseDir  = dirname($rootDir);           // root project
        $upRelDir = 'uploads';
        $upAbsDir = $baseDir . DIRECTORY_SEPARATOR . $upRelDir;
        if (!is_dir($upAbsDir)) {
            if (!@mkdir($upAbsDir, 0755, true) && !is_dir($upAbsDir)) {
                throw new Exception('Gagal membuat folder uploads.');
            }
        }

        // helper nama file aman & unik
        $safe = function(string $original): string {
            $pi   = pathinfo($original);
            $ext  = strtolower($pi['extension'] ?? '');
            $base = preg_replace('/[^a-z0-9\-_.]+/i', '-', $pi['filename'] ?? 'file');
            $rnd  = bin2hex(random_bytes(4));
            return date('Ymd_His') . "-{$rnd}-" . trim($base, '-') . ($ext ? '.'.$ext : '');
        };

        // 3) Upload LOGO
        if (isset($_FILES['img']) && is_uploaded_file($_FILES['img']['tmp_name'])) {
            $newRel = $upRelDir . '/' . $safe($_FILES['img']['name']);
            $newAbs = $baseDir . DIRECTORY_SEPARATOR . $newRel;
            if (!move_uploaded_file($_FILES['img']['tmp_name'], $newAbs)) {
                throw new Exception('Gagal upload logo.');
            }
            if (!empty($_SESSION['system_info']['logo'])) {
                $old = $baseDir . DIRECTORY_SEPARATOR . $_SESSION['system_info']['logo'];
                if (is_file($old)) @unlink($old);
            }
            $this->conn->query(
                "INSERT INTO system_info (meta_field, meta_value)
                 VALUES('logo','{$this->conn->real_escape_string($newRel)}')
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)"
            );
        }

        // 4) Upload BANNER
        if (isset($_FILES['img_banner']) && is_uploaded_file($_FILES['img_banner']['tmp_name'])) {
            $newRel = $upRelDir . '/' . $safe($_FILES['img_banner']['name']);
            $newAbs = $baseDir . DIRECTORY_SEPARATOR . $newRel;
            if (!move_uploaded_file($_FILES['img_banner']['tmp_name'], $newAbs)) {
                throw new Exception('Gagal upload banner.');
            }
            if (!empty($_SESSION['system_info']['banner'])) {
                $old = $baseDir . DIRECTORY_SEPARATOR . $_SESSION['system_info']['banner'];
                if (is_file($old)) @unlink($old);
            }
            $this->conn->query(
                "INSERT INTO system_info (meta_field, meta_value)
                 VALUES('banner','{$this->conn->real_escape_string($newRel)}')
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)"
            );
        }

        // 5) File about / privacy
        if (isset($_POST['about']))   file_put_contents(base_app.'about.html',  $_POST['about']);
        if (isset($_POST['privacy'])) file_put_contents(base_app.'privacy.html', $_POST['privacy']);

        // refresh cache & flash
        $this->update_system_info();
        $this->set_flashdata('success','System Info Successfully Updated.');

        return json_encode(['status' => 'success']);
    } catch (Throwable $e) {
        http_response_code(500);
        return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}


	function set_userdata($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['userdata'][$field]= $value;
		}
	}
	function userdata($field = ''){
		if(!empty($field)){
			if(isset($_SESSION['userdata'][$field]))
				return $_SESSION['userdata'][$field];
			else
				return null;
		}else{
			return false;
		}
	}
	function set_flashdata($flash='',$value=''){
		if(!empty($flash) && !empty($value)){
			$_SESSION['flashdata'][$flash]= $value;
		return true;
		}
	}
	function chk_flashdata($flash = ''){
		if(isset($_SESSION['flashdata'][$flash])){
			return true;
		}else{
			return false;
		}
	}
	function flashdata($flash = ''){
		if(!empty($flash)){
			$_tmp = $_SESSION['flashdata'][$flash];
			unset($_SESSION['flashdata']);
			return $_tmp;
		}else{
			return false;
		}
	}
	function sess_des(){
		if(isset($_SESSION['userdata'])){
				unset($_SESSION['userdata']);
			return true;
		}
			return true;
	}
	function info($field=''){
		if(!empty($field)){
			if(isset($_SESSION['system_info'][$field]))
				return $_SESSION['system_info'][$field];
			else
				return false;
		}else{
			return false;
		}
	}
	function set_info($field='',$value=''){
		if(!empty($field) && !empty($value)){
			$_SESSION['system_info'][$field] = $value;
		}
	}
}
$_settings = new SystemSettings();
$_settings->load_system_info();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'update_settings':
    header('Content-Type: application/json');
    echo $sysset->update_settings_info();
    break;

	default:
		// echo $sysset->index();
		break;
}
?>