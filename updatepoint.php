<?php
include('DB_Function.php');
include('Data_Function.php');
include('DB_Login.php');
session_start();
$logfile = 'log/map_edit_' . date('Ymd', time()) . '.txt';
$_SESSION['AGV_User']['Name']='WebMap';
// 接收 fetch 傳遞的目錄路徑參數
	//$directory = $data->directory']; 
		//$Status['Status']=$data->Tag_ID'];
		$db='agvslam';
		
		$postData = file_get_contents("php://input");
		$data = json_decode($postData);

		if ($data->CmdType=='INSERT')
		{

			$Query = "INSERT INTO `point` (`Tag_ID`, `Tag_name`, `X`, `Y`, `Z`, `tag_type`, `floor`, `MFG_tag`,shelf_car_type,floor_no,slam,Retreat_Flag) 
					VALUES ('" . $data->Point->tagID . "', '" . $data->Point->tagName. "', '" . $data->Point->x . "', '" . $data->Point->y . "','" . $data->Point->z . "', 'N', '" . $data->Point->floor . "', '" . $data->Point->MFG_tag . "',
					'" . $data->Point->shelf_car_type . "','" . $data->Point->floorid . "','" . $data->Point->PositionNotice . "','".$data->Point->Retreat_Flag."')";
			$Status['Status']=$Query;
			$Ans = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "INSERT", $Query);
			if ($Ans > 0) {
						//echo '新增點位'.$Query.':'.$Ans.'<br>';
						//$Status['Status']='insert:'.$Ans;
			} else {
						$Query = "update `point` set `Tag_name`='" . $data->Point->tagName . "',X='" . $data->Point->x . "',Y='" . $data->Point->y . "',Z='" . $data->Point->z . "',slam='" . $data->Point->PositionNotice . "',Retreat_Flag=".$data->Point->Retreat_Flag.",
						floor='".$data->Point->floor."',MFG_tag='" . $data->Point->MFG_tag . "',shelf_car_type='" . $data->Point->shelf_car_type . "',floor_no='" . $data->Point->floorid . "' where Tag_ID='" . $data->Point->tagID . "'";
						$Ans = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "UPDATE", $Query);
						$Status['Status']=$Query;
			}
		}	
		elseif($data->CmdType=='DELETE')
		{
				$Query = "DELETE FROM `point` WHERE `Tag_ID`='" . $data->Tag_ID . "'";
				$Ans1 = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "DELETE", $Query);
				//echo '刪除點位'.$Query.':'.$Ans.'<br>';		
				//Write_Log($logfile, $_SESSION['AGV_User']['Name'] . "刪除點位:(" . $db . ")" . $Query);
				$Query = "DELETE FROM `path` WHERE `From_Point`='" . $data->Tag_ID . "' or `To_Point`='" . $data->Tag_ID . "'";
				$Ans2 = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "DELETE", $Query);
				//Write_Log($logfile, $_SESSION['AGV_User']['Name'] . "刪除路徑:(" . $db . ")" . $Query);
				$Status['Status']="Delete:".$Ans1.'-'.$Ans2;
		}elseif($data->CmdType=='ADDPATH')
		{
			$Query = "INSERT INTO `path` (`From_Point`, `To_Point`, `Forward_Sensor`, `Forward_Speed`, `backward_Sensor`, `backward_Speed`, `active`, `path_type`,LM_USER,FORK_BACK,Forward_time,backward_time,distance,Priwt) 
						VALUES ( '" .$data->Path->From_Point. "', '" . $data->Path->To_Point. "', '" . $data->Path->Forward_Sensor. "', '" . $data->Path->Forward_Speed. "', '" . $data->Path->backward_Sensor. "', '" . $data->Path->backward_Speed. "', '1', '" . $data->Path->path_type. "','" . $_SESSION['AGV_User']['Name'] . "', '".$data->Path->FORK_BACK."',0,0,0,50)";
			$Ans = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "INSERT", $Query);
					//echo 'add '.$Query.':'.$Ans.'<br>';
			//Write_Log($logfile, $_SESSION['AGV_User']['Name'] . "新增路徑:(" . $db . ")" . $Query);
			$Status['Status']="ADDPATH:".$Ans;
			if ($Ans <= 0) {
				$Query = "UPDATE `path` SET Forward_Sensor = '" . $data->Path->Forward_Sensor. "',Forward_Speed = '" . $data->Path->Forward_Speed. "' ,backward_Sensor = '" . $data->Path->backward_Sensor. "' ,backward_Speed = '" . $data->Path->backward_Speed. "' ,path_type = '" . $data->Path->path_type. "',FORK_BACK = '" . $data->Path->FORK_BACK. "' ,active=1,LM_USER='" . $_SESSION['AGV_User']['Name'] . "'
							WHERE From_Point='" . $data->Path->From_Point. "' and To_Point='" . $data->Path->To_Point. "'";
						$Ans = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "UPDATE", $Query);
						//echo '更新路徑'.$Query.':'.$Ans.'<br>';
				//Write_Log($logfile, $_SESSION['AGV_User']['Name'] . "更新路徑:(" . $db . ")" . $Query);
				$Status['Status']="Update:".$Ans;
			}
			//$Status['Status']="ADDPATH:".$Ans;
		}elseif($data->CmdType=='DELETEPATH')
		{

				$Query = "DELETE FROM `path` WHERE `From_Point`='" . $data->From_Point . "' and `To_Point`='" . $data->To_Point . "'";
				$Ans = MySQL_UTF8_Function($Local_Host, $Local_User, $Local_Password, $db, "DELETE", $Query);
				//Write_Log($logfile, $_SESSION['AGV_User']['Name'] . "刪除路徑:(" . $db . ")" . $Query);
				$Status['Status']="Delete:".$Ans;
		}
		

			
			
// 輸出回傳結果
		echo json_encode($Status);

?>
